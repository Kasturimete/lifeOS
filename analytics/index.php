<?php

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION["user_id"];


/* =========================
   GOALS
========================= */

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total,
        COALESCE(AVG(progress), 0) AS avg_progress
    FROM goals
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$goalData = $stmt->get_result()->fetch_assoc();

$totalGoals = $goalData["total"];
$goalProgress = round($goalData["avg_progress"]);

$stmt->close();


/* =========================
   TASKS
========================= */

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
    FROM tasks
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$taskData = $stmt->get_result()->fetch_assoc();

$totalTasks = $taskData["total"];
$completedTasks = $taskData["completed"] ?? 0;

$taskProgress = $totalTasks > 0
    ? round(($completedTasks / $totalTasks) * 100)
    : 0;

$stmt->close();


/* =========================
   HABITS
   Last 7 Days
========================= */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM habits
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$totalHabits = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


$stmt = $conn->prepare("
    SELECT COUNT(*) AS completed
    FROM habit_logs
    WHERE user_id = ?
    AND completed_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$habitLogs = $stmt->get_result()->fetch_assoc()["completed"];

$stmt->close();


$possibleHabitLogs = $totalHabits * 7;

$habitProgress = $possibleHabitLogs > 0
    ? round(($habitLogs / $possibleHabitLogs) * 100)
    : 0;


/* =========================
   FINANCE
========================= */

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END), 0) AS income,
        COALESCE(SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END), 0) AS expense
    FROM transactions
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$financeData = $stmt->get_result()->fetch_assoc();

$totalIncome = $financeData["income"];
$totalExpense = $financeData["expense"];
$balance = $totalIncome - $totalExpense;

$stmt->close();


/* =========================
   EXPENSE CATEGORIES
========================= */

$stmt = $conn->prepare("
    SELECT category, SUM(amount) AS total
    FROM transactions
    WHERE user_id = ?
    AND type = 'Expense'
    GROUP BY category
    ORDER BY total DESC
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$expenseResult = $stmt->get_result();

$expenseCategories = [];
$expenseAmounts = [];

while ($row = $expenseResult->fetch_assoc()) {

    $expenseCategories[] = $row["category"];
    $expenseAmounts[] = $row["total"];
}

$stmt->close();


/* =========================
   LEARNING
========================= */

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(AVG(progress), 0) AS progress,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
    FROM courses
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$learningData = $stmt->get_result()->fetch_assoc();

$totalCourses = $learningData["total"];
$learningProgress = round($learningData["progress"]);
$completedCourses = $learningData["completed"] ?? 0;

$stmt->close();


/* =========================
   CAREER / SKILLS
========================= */

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(AVG(progress), 0) AS progress
    FROM skills
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$careerData = $stmt->get_result()->fetch_assoc();

$totalSkills = $careerData["total"];
$skillProgress = round($careerData["progress"]);

$stmt->close();


/* =========================
   JOB APPLICATIONS
========================= */

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Selected' THEN 1 ELSE 0 END) AS selected,
        SUM(CASE WHEN status = 'Interview' THEN 1 ELSE 0 END) AS interview
    FROM job_applications
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$jobData = $stmt->get_result()->fetch_assoc();

$totalApplications = $jobData["total"];
$selectedJobs = $jobData["selected"] ?? 0;
$interviewJobs = $jobData["interview"] ?? 0;

$stmt->close();


/* =========================
   LIFE SCORE
========================= */

$lifeScore = round(
    (
        $goalProgress +
        $taskProgress +
        $habitProgress +
        $learningProgress
    ) / 4
);


/* =========================
   STATUS MESSAGE
========================= */

if ($lifeScore >= 80) {

    $scoreMessage = "Excellent! You are managing your life very well. 🚀";

} elseif ($lifeScore >= 60) {

    $scoreMessage = "Good progress! Keep improving your daily consistency. 💪";

} elseif ($lifeScore >= 40) {

    $scoreMessage = "You are making progress. Focus on small daily improvements. 🌱";

} else {

    $scoreMessage = "Let's build better habits and routines step by step. ✨";
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Analytics | LifeOS</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #0d0b1f;
    color: white;
}

.container {
    width: 92%;
    max-width: 1250px;
    margin: auto;
    padding: 30px 0 50px;
}


/* HEADER */

.top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.logo {
    font-size: 28px;
    font-weight: bold;
    color: #b78cff;
}

.back {
    color: white;
    text-decoration: none;
    background: #211b3d;
    padding: 10px 18px;
    border-radius: 10px;
}

.back:hover {
    background: #30275a;
}


/* TITLE */

h1 {
    font-size: 34px;
    margin-bottom: 8px;
}

.subtitle {
    color: #aaa4c0;
    margin-bottom: 30px;
}


/* SCORE */

.score-section {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

.score-card,
.insight-card,
.analytics-card,
.stat-card {
    background: #17132d;
    border: 1px solid #2c2550;
    border-radius: 20px;
}


/* LIFE SCORE */

.score-card {
    padding: 25px;
    text-align: center;
}

.score-card h2 {
    margin-bottom: 20px;
}

.score-circle {
    width: 170px;
    height: 170px;
    margin: auto;
    border-radius: 50%;

    background: conic-gradient(
        #a66cff var(--progress),
        #2b2545 var(--progress)
    );

    display: flex;
    align-items: center;
    justify-content: center;
}

.score-inner {
    width: 135px;
    height: 135px;
    background: #17132d;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.score-number {
    font-size: 38px;
    font-weight: bold;
    color: #c49aff;
}

.score-label {
    color: #aaa4c0;
    font-size: 13px;
}


/* INSIGHT */

.insight-card {
    padding: 28px;
}

.insight-card h2 {
    margin-bottom: 15px;
}

.ai-box {
    background: #211b3d;
    border: 1px solid #49357c;
    padding: 20px;
    border-radius: 15px;
    line-height: 1.7;
    color: #d5ccef;
}

.ai-title {
    color: #c49aff;
    font-weight: bold;
    margin-bottom: 8px;
}


/* STATISTICS */

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    padding: 22px;
}

.stat-card h3 {
    color: #aaa4c0;
    font-size: 14px;
    margin-bottom: 10px;
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #b78cff;
}

.stat-small {
    color: #77718d;
    font-size: 12px;
    margin-top: 6px;
}


/* ANALYTICS GRID */

.analytics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.analytics-card {
    padding: 25px;
}

.analytics-card h2 {
    margin-bottom: 20px;
}

.chart-container {
    position: relative;
    height: 300px;
}


/* CATEGORY PROGRESS */

.progress-item {
    margin-bottom: 22px;
}

.progress-top {
    display: flex;
    justify-content: space-between;
    margin-bottom: 7px;
}

.progress-name {
    color: #ddd8e8;
}

.progress-value {
    color: #b78cff;
}

.progress-bg {
    height: 9px;
    background: #2b2545;
    border-radius: 10px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(
        90deg,
        #8f5cff,
        #c06cff
    );
    border-radius: 10px;
}


/* FINANCE */

.finance-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.finance-box {
    background: #211b3d;
    padding: 18px;
    border-radius: 12px;
}

.finance-box small {
    color: #aaa4c0;
}

.finance-box strong {
    display: block;
    font-size: 20px;
    margin-top: 7px;
}

.income {
    color: #55e6a5;
}

.expense {
    color: #ff6b8a;
}

.balance {
    color: #b78cff;
}


/* RESPONSIVE */

@media(max-width:950px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .score-section {
        grid-template-columns: 1fr;
    }

    .analytics-grid {
        grid-template-columns: 1fr;
    }

}

@media(max-width:600px) {

    .stats {
        grid-template-columns: 1fr;
    }

    .finance-row {
        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>

<div class="container">


<!-- HEADER -->

<div class="top">

    <div class="logo">
        ✦ LifeOS
    </div>

    <a
        href="../dashboard.php"
        class="back"
    >
        ← Dashboard
    </a>

</div>


<!-- TITLE -->

<h1>Analytics 📊</h1>

<p class="subtitle">
    Understand your life, track your progress and make better decisions.
</p>


<!-- LIFE SCORE -->

<div class="score-section">


    <div class="score-card">

        <h2>Life Score</h2>

        <div class="score-circle" style="--progress: <?php echo $lifeScore; ?>%;">

            <div class="score-inner">

                <div class="score-number">
                    <?php echo $lifeScore; ?>
                </div>

                <div class="score-label">
                    / 100
                </div>

            </div>

        </div>

    </div>


    <div class="insight-card">

        <h2>✨ LifeOS Insight</h2>

        <div class="ai-box">

            <div class="ai-title">
                Your Current Progress
            </div>

            <?php echo $scoreMessage; ?>

            <br><br>

            Your current goal progress is
            <strong><?php echo $goalProgress; ?>%</strong>,

            task completion is
            <strong><?php echo $taskProgress; ?>%</strong>,

            habit consistency is
            <strong><?php echo $habitProgress; ?>%</strong>,

            and learning progress is
            <strong><?php echo $learningProgress; ?>%</strong>.

        </div>

    </div>

</div>


<!-- STAT CARDS -->

<div class="stats">


    <div class="stat-card">

        <h3>🎯 Goals</h3>

        <div class="stat-number">
            <?php echo $goalProgress; ?>%
        </div>

        <div class="stat-small">
            <?php echo $totalGoals; ?> total goals
        </div>

    </div>


    <div class="stat-card">

        <h3>✅ Tasks</h3>

        <div class="stat-number">
            <?php echo $completedTasks; ?>
        </div>

        <div class="stat-small">
            <?php echo $taskProgress; ?>% completed
        </div>

    </div>


    <div class="stat-card">

        <h3>📚 Learning</h3>

        <div class="stat-number">
            <?php echo $learningProgress; ?>%
        </div>

        <div class="stat-small">
            <?php echo $completedCourses; ?> completed courses
        </div>

    </div>


    <div class="stat-card">

        <h3>🧠 Skills</h3>

        <div class="stat-number">
            <?php echo $skillProgress; ?>%
        </div>

        <div class="stat-small">
            <?php echo $totalSkills; ?> skills tracked
        </div>

    </div>

</div>


<!-- ANALYTICS -->

<div class="analytics-grid">


    <!-- LIFE AREAS -->

    <div class="analytics-card">

        <h2>📈 Life Areas</h2>


        <div class="progress-item">

            <div class="progress-top">

                <span class="progress-name">
                    Goals
                </span>

                <span class="progress-value">
                    <?php echo $goalProgress; ?>%
                </span>

            </div>

            <div class="progress-bg">

                <div
                    class="progress-bar"
                    style="width: <?php echo $goalProgress; ?>%;"
                ></div>

            </div>

        </div>


        <div class="progress-item">

            <div class="progress-top">

                <span class="progress-name">
                    Tasks
                </span>

                <span class="progress-value">
                    <?php echo $taskProgress; ?>%
                </span>

            </div>

            <div class="progress-bg">

                <div
                    class="progress-bar"
                    style="width: <?php echo $taskProgress; ?>%;"
                ></div>

            </div>

        </div>


        <div class="progress-item">

            <div class="progress-top">

                <span class="progress-name">
                    Habits
                </span>

                <span class="progress-value">
                    <?php echo $habitProgress; ?>%
                </span>

            </div>

            <div class="progress-bg">

                <div
                    class="progress-bar"
                    style="width: <?php echo $habitProgress; ?>%;"
                ></div>

            </div>

        </div>


        <div class="progress-item">

            <div class="progress-top">

                <span class="progress-name">
                    Learning
                </span>

                <span class="progress-value">
                    <?php echo $learningProgress; ?>%
                </span>

            </div>

            <div class="progress-bg">

                <div
                    class="progress-bar"
                    style="width: <?php echo $learningProgress; ?>%;"
                ></div>

            </div>

        </div>


        <div class="progress-item">

            <div class="progress-top">

                <span class="progress-name">
                    Career Skills
                </span>

                <span class="progress-value">
                    <?php echo $skillProgress; ?>%
                </span>

            </div>

            <div class="progress-bg">

                <div
                    class="progress-bar"
                    style="width: <?php echo $skillProgress; ?>%;"
                ></div>

            </div>

        </div>

    </div>


    <!-- LIFE AREA CHART -->

    <div class="analytics-card">

        <h2>🎯 Performance Overview</h2>

        <div class="chart-container">

            <canvas id="performanceChart"></canvas>

        </div>

    </div>


    <!-- FINANCE -->

    <div class="analytics-card">

        <h2>💰 Financial Overview</h2>


        <div class="finance-row">

            <div class="finance-box">

                <small>Income</small>

                <strong class="income">
                    ₹<?php echo number_format($totalIncome, 2); ?>
                </strong>

            </div>


            <div class="finance-box">

                <small>Expenses</small>

                <strong class="expense">
                    ₹<?php echo number_format($totalExpense, 2); ?>
                </strong>

            </div>


            <div class="finance-box">

                <small>Balance</small>

                <strong class="balance">
                    ₹<?php echo number_format($balance, 2); ?>
                </strong>

            </div>

        </div>


        <div class="chart-container">

            <canvas id="financeChart"></canvas>

        </div>

    </div>


    <!-- CAREER -->

    <div class="analytics-card">

        <h2>💼 Career Overview</h2>


        <div class="progress-item">

            <div class="progress-top">

                <span class="progress-name">
                    Skill Development
                </span>

                <span class="progress-value">
                    <?php echo $skillProgress; ?>%
                </span>

            </div>

            <div class="progress-bg">

                <div
                    class="progress-bar"
                    style="width: <?php echo $skillProgress; ?>%;"
                ></div>

            </div>

        </div>


        <p style="color:#aaa4c0; margin-top:20px;">

            🏢 Job Applications:
            <strong style="color:white;">
                <?php echo $totalApplications; ?>
            </strong>

        </p>


        <p style="color:#aaa4c0; margin-top:15px;">

            🎤 Interviews:
            <strong style="color:white;">
                <?php echo $interviewJobs; ?>
            </strong>

        </p>


        <p style="color:#aaa4c0; margin-top:15px;">

            🎉 Selected:
            <strong style="color:#55e6a5;">
                <?php echo $selectedJobs; ?>
            </strong>

        </p>

    </div>

</div>


</div>


<script>

/* =========================
   PERFORMANCE CHART
========================= */

const performanceChart =
    document.getElementById("performanceChart");


new Chart(performanceChart, {

    type: "radar",

    data: {

        labels: [
            "Goals",
            "Tasks",
            "Habits",
            "Learning",
            "Career"
        ],

        datasets: [{

            label: "Life Progress",

            data: [
                <?php echo $goalProgress; ?>,
                <?php echo $taskProgress; ?>,
                <?php echo $habitProgress; ?>,
                <?php echo $learningProgress; ?>,
                <?php echo $skillProgress; ?>
            ],

            borderWidth: 2,

            pointRadius: 4

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        scales: {

            r: {

                beginAtZero: true,

                max: 100,

                ticks: {
                    color: "#aaa4c0"
                },

                grid: {
                    color: "#39315e"
                },

                angleLines: {
                    color: "#39315e"
                },

                pointLabels: {
                    color: "white"
                }

            }

        },

        plugins: {

            legend: {

                labels: {
                    color: "white"
                }

            }

        }

    }

});


/* =========================
   FINANCE CHART
========================= */

const financeChart =
    document.getElementById("financeChart");


new Chart(financeChart, {

    type: "bar",

    data: {

        labels: [
            "Income",
            "Expenses",
            "Balance"
        ],

        datasets: [{

            label: "Amount",

            data: [
                <?php echo $totalIncome; ?>,
                <?php echo $totalExpense; ?>,
                <?php echo $balance; ?>
            ],

            borderWidth: 0

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        scales: {

            y: {

                beginAtZero: true,

                ticks: {
                    color: "#aaa4c0"
                },

                grid: {
                    color: "#2c2550"
                }

            },

            x: {

                ticks: {
                    color: "white"
                },

                grid: {
                    display: false
                }

            }

        },

        plugins: {

            legend: {

                labels: {
                    color: "white"
                }

            }

        }

    }

});

</script>

</body>

</html>