<?php

session_start();

require_once "config/db.php";


/* =========================================
   LOGIN CHECK
========================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}


$userId = $_SESSION["user_id"];
$userName = $_SESSION["user_name"];


/* =========================================
   DEFAULT VALUES
========================================= */

$goalProgress = 0;
$tasksDone = 0;
$savings = 0;
$habitStreak = 0;


/* =========================================
   GOAL PROGRESS
========================================= */

$sql = "
SELECT AVG(progress) AS avg_progress
FROM goals
WHERE user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && $row["avg_progress"] !== null) {
    $goalProgress = round($row["avg_progress"]);
}


/* =========================================
   COMPLETED TASKS
========================================= */

$sql = "
SELECT COUNT(*) AS total
FROM tasks
WHERE user_id = ?
AND status = 'Completed'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    $tasksDone = $row["total"];
}


/* =========================================
   FINANCE
========================================= */

$sql = "
SELECT
SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END) AS income,
SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END) AS expense
FROM transactions
WHERE user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$income = $row["income"] ?? 0;
$expense = $row["expense"] ?? 0;

$savings = $income - $expense;


/* =========================================
   HABIT STREAK
========================================= */

$sql = "
SELECT MAX(current_streak) AS max_streak
FROM habits
WHERE user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && $row["max_streak"] !== null) {
    $habitStreak = $row["max_streak"];
}


/* =========================================
   LIFE SCORE
========================================= */

$taskScore = 0;

$sql = "
SELECT
COUNT(*) AS total,
SUM(status = 'Completed') AS completed
FROM tasks
WHERE user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && $row["total"] > 0) {
    $taskScore = round(
        ($row["completed"] / $row["total"]) * 100
    );
}


$lifeScore = round(
    (
        $goalProgress +
        $taskScore +
        min($habitStreak * 5, 100)
    ) / 3
);

if ($lifeScore > 100) {
    $lifeScore = 100;
}


/* =========================================
   TODAY'S TASKS
========================================= */

$todayTasks = [];

$sql = "
SELECT id, title, priority, status
FROM tasks
WHERE user_id = ?
AND due_date = CURDATE()
ORDER BY priority DESC
LIMIT 5
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $todayTasks[] = $row;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>LifeOS Dashboard</title>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}


body {

    background:
        radial-gradient(
            circle at top left,
            #24104d,
            transparent 35%
        ),
        #080611;

    color: white;

    min-height: 100vh;

}


/* =========================================
   LAYOUT
========================================= */

.layout {

    display: flex;

    min-height: 100vh;

}


/* =========================================
   SIDEBAR
========================================= */

.sidebar {

    width: 250px;

    background: rgba(12, 9, 25, 0.96);

    border-right:
        1px solid
        rgba(255,255,255,0.08);

    padding: 25px 18px;

    position: fixed;

    top: 0;
    bottom: 0;
    left: 0;

}


.logo {

    font-size: 26px;

    font-weight: bold;

    margin-bottom: 35px;

}


.logo span {

    color: #a855f7;

}


.menu-title {

    color: #666;

    font-size: 10px;

    letter-spacing: 2px;

    margin:
        20px 10px 10px;

}


.sidebar a {

    display: block;

    color: #999;

    text-decoration: none;

    padding: 13px 14px;

    margin: 5px 0;

    border-radius: 12px;

    transition: 0.3s;

}


.sidebar a:hover {

    background:
        rgba(139,92,246,0.15);

    color: white;

    transform:
        translateX(4px);

}


.sidebar a.active {

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #9333ea
        );

    color: white;

    box-shadow:
        0 8px 25px
        rgba(124,58,237,0.3);

}


/* =========================================
   MAIN
========================================= */

.main {

    margin-left: 250px;

    width: calc(100% - 250px);

    padding: 30px;

}


/* =========================================
   TOPBAR
========================================= */

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding-bottom: 25px;

    border-bottom:
        1px solid
        rgba(255,255,255,0.06);

}


.search {

    width: 400px;

    padding: 14px 18px;

    background:
        rgba(255,255,255,0.03);

    border:
        1px solid
        rgba(255,255,255,0.08);

    border-radius: 12px;

    color: #aaa;

}


.profile {

    display: flex;

    align-items: center;

    gap: 12px;

}


.avatar {

    width: 42px;

    height: 42px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #9333ea,
            #6366f1
        );

    font-weight: bold;

}


/* =========================================
   WELCOME
========================================= */

.welcome {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin:
        40px 0 30px;

}


.welcome small {

    color: #777;

    letter-spacing: 2px;

}


.welcome h1 {

    font-size: 34px;

    margin-top: 10px;

}


.welcome h1 span {

    color: #a855f7;

}


.welcome p {

    color: #888;

    margin-top: 8px;

}


.ai-status {

    padding: 12px 18px;

    border:
        1px solid
        rgba(168,85,247,0.5);

    border-radius: 12px;

    color: #c084fc;

    background:
        rgba(168,85,247,0.08);

}


/* =========================================
   CARDS
========================================= */

.cards {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;

}


.card {

    background:
        rgba(18,14,34,0.9);

    border:
        1px solid
        rgba(255,255,255,0.08);

    border-radius: 18px;

    padding: 22px;

}


.card-icon {

    font-size: 25px;

    margin-bottom: 18px;

}


.card-title {

    color: #888;

    font-size: 13px;

}


.card-value {

    font-size: 30px;

    font-weight: bold;

    margin-top: 8px;

}


.card-change {

    color: #22c55e;

    font-size: 12px;

    margin-top: 10px;

}


/* =========================================
   CONTENT GRID
========================================= */

.content-grid {

    display: grid;

    grid-template-columns:
        2fr 1fr;

    gap: 20px;

    margin-top: 20px;

}


.panel {

    background:
        rgba(18,14,34,0.9);

    border:
        1px solid
        rgba(255,255,255,0.08);

    border-radius: 18px;

    padding: 25px;

}


.panel h2 {

    font-size: 20px;

    margin-bottom: 20px;

}


/* =========================================
   ACTIVITY
========================================= */

.activity {

    height: 250px;

    display: flex;

    align-items: end;

    gap: 15px;

    padding-top: 30px;

}


.bar {

    flex: 1;

    background:
        linear-gradient(
            to top,
            #7c3aed,
            #c084fc
        );

    border-radius:
        8px 8px 0 0;

    opacity: 0.8;

}


.days {

    display: flex;

    gap: 15px;

    margin-top: 10px;

}


.days span {

    flex: 1;

    text-align: center;

    color: #666;

    font-size: 11px;

}


/* =========================================
   LIFE SCORE
========================================= */

.score {

    text-align: center;

}


.score-circle {

    width: 170px;

    height: 170px;

    margin: 20px auto;

    border-radius: 50%;

    background:
        conic-gradient(
            #a855f7 <?php echo $lifeScore; ?>%,
            #2b2545 <?php echo $lifeScore; ?>%
        );

    display: flex;

    align-items: center;

    justify-content: center;

}


.score-inner {

    width: 130px;

    height: 130px;

    border-radius: 50%;

    background: #100c20;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

}


.score-inner strong {

    font-size: 35px;

}


.score-inner span {

    color: #a855f7;

    font-size: 12px;

}


/* =========================================
   BOTTOM
========================================= */

.bottom-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 20px;

    margin-top: 20px;

}


.task {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 14px;

    margin-bottom: 10px;

    background:
        rgba(255,255,255,0.03);

    border-radius: 10px;

}


.task-status {

    width: 10px;

    height: 10px;

    border-radius: 50%;

    background: #a855f7;

}


.empty {

    color: #777;

    padding: 20px 0;

}


/* =========================================
   AI CARD
========================================= */

.ai-card {

    background:
        linear-gradient(
            135deg,
            rgba(124,58,237,0.2),
            rgba(59,130,246,0.08)
        );

}


.ai-card p {

    color: #aaa;

    line-height: 1.7;

}


.ai-button {

    display: inline-block;

    margin-top: 20px;

    padding: 11px 18px;

    border-radius: 10px;

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #9333ea
        );

    color: white;

    text-decoration: none;

}


/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:1100px) {

    .cards {

        grid-template-columns:
            repeat(2, 1fr);

    }

    .content-grid {

        grid-template-columns: 1fr;

    }

}


@media(max-width:800px) {

    .sidebar {

        display: none;

    }

    .main {

        margin-left: 0;

        width: 100%;

        padding: 20px;

    }

    .cards {

        grid-template-columns: 1fr;

    }

    .bottom-grid {

        grid-template-columns: 1fr;

    }

    .search {

        width: 200px;

    }

}


</style>

</head>


<body>


<div class="layout">


<!-- =====================================
     SIDEBAR
===================================== -->

<aside class="sidebar">


<div class="logo">

    Life<span>OS</span>

</div>


<div class="menu-title">
    MAIN
</div>


<a href="dashboard.php" class="active">
    🏠 Dashboard
</a>


<a href="goals/">
    🎯 Goals
</a>


<a href="planner/">
    📋 Planner
</a>


<a href="habits/">
    🔥 Habits
</a>


<a href="finance/">
    💰 Finance
</a>


<a href="learning/">
    📚 Learning
</a>


<a href="career/">
    💼 Career
</a>


<a href="notes/">
    📝 Notes
</a>


<div class="menu-title">
    INTELLIGENCE
</div>


<a href="analytics/">
    📊 Analytics
</a>


<a href="ai/">
    🤖 AI Copilot
</a>


<div class="menu-title">
    ACCOUNT
</div>


<a href="logout.php">
    🚪 Logout
</a>


</aside>



<!-- =====================================
     MAIN
===================================== -->

<main class="main">


<!-- TOPBAR -->

<div class="topbar">


<input
    class="search"
    type="text"
    placeholder="🔍 Search your life..."
>


<div class="profile">


    <span>🔔</span>


    <div class="avatar">

        <?php
        echo strtoupper(
            substr($userName, 0, 1)
        );
        ?>

    </div>


    <div>

        <strong>
            <?php
            echo htmlspecialchars($userName);
            ?>
        </strong>

        <br>

        <small style="color:#777;">
            Personal
        </small>

    </div>


</div>

</div>



<!-- WELCOME -->

<div class="welcome">


<div>

    <small>
        <?php echo strtoupper(date("l • F d, Y")); ?>
    </small>


    <h1>

        Good morning,
        <span>
            <?php
            echo htmlspecialchars($userName);
            ?>
        </span>
        👋

    </h1>


    <p>
        Here's what's happening in your life today.
    </p>

</div>


<div class="ai-status">

    🟢 AI Active

</div>


</div>



<!-- =====================================
     STAT CARDS
===================================== -->

<div class="cards">


<div class="card">

    <div class="card-icon">
        🎯
    </div>

    <div class="card-title">
        Goal Progress
    </div>

    <div class="card-value">
        <?php echo $goalProgress; ?>%
    </div>

    <div class="card-change">
        ↑ Keep going
    </div>

</div>



<div class="card">

    <div class="card-icon">
        ☑️
    </div>

    <div class="card-title">
        Tasks Completed
    </div>

    <div class="card-value">
        <?php echo $tasksDone; ?>
    </div>

    <div class="card-change">
        ↑ Productivity
    </div>

</div>



<div class="card">

    <div class="card-icon">
        ₹
    </div>

    <div class="card-title">
        Total Savings
    </div>

    <div class="card-value">
        ₹<?php echo number_format($savings, 0); ?>
    </div>

    <div class="card-change">
        ↑ Finance
    </div>

</div>



<div class="card">

    <div class="card-icon">
        🔥
    </div>

    <div class="card-title">
        Habit Streak
    </div>

    <div class="card-value">
        <?php echo $habitStreak; ?>
    </div>

    <div class="card-change">
        days active
    </div>

</div>


</div>



<!-- =====================================
     CONTENT
===================================== -->

<div class="content-grid">


<!-- ACTIVITY -->

<div class="panel">

    <h2>
        📈 Weekly Activity
    </h2>


    <div class="activity">

        <div class="bar" style="height:40%;"></div>

        <div class="bar" style="height:55%;"></div>

        <div class="bar" style="height:45%;"></div>

        <div class="bar" style="height:70%;"></div>

        <div class="bar" style="height:60%;"></div>

        <div class="bar" style="height:85%;"></div>

        <div class="bar" style="height:75%;"></div>

    </div>


    <div class="days">

        <span>Mon</span>
        <span>Tue</span>
        <span>Wed</span>
        <span>Thu</span>
        <span>Fri</span>
        <span>Sat</span>
        <span>Sun</span>

    </div>

</div>



<!-- LIFE SCORE -->

<div class="panel score">

    <h2>
        🤖 AI Life Score
    </h2>


    <div class="score-circle">

        <div class="score-inner">

            <strong>
                <?php echo $lifeScore; ?>
            </strong>

            <span>
                /100
            </span>

        </div>

    </div>


    <p style="color:#aaa;">
        Overall balance of your LifeOS activities
    </p>

</div>


</div>



<!-- =====================================
     BOTTOM
===================================== -->

<div class="bottom-grid">


<!-- TODAY'S TASKS -->

<div class="panel">

    <h2>
        📋 Today's Focus
    </h2>


    <?php if (count($todayTasks) > 0): ?>


        <?php foreach ($todayTasks as $task): ?>


        <div class="task">

            <div class="task-status"></div>


            <div>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $task["title"]
                    );
                    ?>
                </strong>


                <br>


                <small style="color:#777;">

                    <?php
                    echo htmlspecialchars(
                        $task["priority"]
                    );
                    ?>

                </small>

            </div>

        </div>


        <?php endforeach; ?>


    <?php else: ?>


        <div class="empty">

            No tasks for today 🎉

            <br><br>

            <a
                href="planner/"
                style="color:#a855f7;"
            >
                Add a task →
            </a>

        </div>


    <?php endif; ?>


</div>



<!-- AI COPILOT -->

<div class="panel ai-card">

    <h2>
        🤖 AI Life Copilot
    </h2>


    <p>

        Your personal LifeOS assistant can help you
        understand your goals, tasks, habits and
        productivity.

    </p>


    <p style="margin-top:10px;">

        Ask:
        <strong>
            "What should I focus on today?"
        </strong>

    </p>


    <a
        href="ai/"
        class="ai-button"
    >

        Explore AI →
        
    </a>

</div>


</div>


</main>

</div>


</body>

</html>