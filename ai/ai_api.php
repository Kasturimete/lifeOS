<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

require_once "../config/db.php";


/* ==========================================
   HELPER FUNCTION
========================================== */

function sendResponse($success, $message)
{
    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);

    exit();
}


/* ==========================================
   LOGIN CHECK
========================================== */

if (!isset($_SESSION["user_id"])) {

    sendResponse(
        false,
        "Please login first."
    );

}

$userId = (int) $_SESSION["user_id"];


/* ==========================================
   QUESTION
========================================== */

$question = isset($_POST["question"])
    ? trim($_POST["question"])
    : "";


if ($question === "") {

    sendResponse(
        false,
        "Please enter a question."
    );

}


$q = strtolower($question);


/* ==========================================
   DEFAULT DATA
========================================== */

$goalProgress = 0;

$totalGoals = 0;

$pendingTasks = 0;

$completedTasks = 0;

$habitCount = 0;

$bestStreak = 0;

$courseCount = 0;

$income = 0;

$expense = 0;


/* ==========================================
   GOALS
========================================== */

$sql = "
    SELECT progress
    FROM goals
    WHERE user_id = ?
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $userId);

    $stmt->execute();

    $result = $stmt->get_result();

    $totalProgress = 0;

    while ($row = $result->fetch_assoc()) {

        $totalGoals++;

        $totalProgress += (int)$row["progress"];

    }

    if ($totalGoals > 0) {

        $goalProgress =
            round($totalProgress / $totalGoals);

    }

    $stmt->close();

}


/* ==========================================
   TASKS
========================================== */

$sql = "
    SELECT status
    FROM tasks
    WHERE user_id = ?
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $userId);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        if (
            strtolower($row["status"]) === "completed"
        ) {

            $completedTasks++;

        } else {

            $pendingTasks++;

        }

    }

    $stmt->close();

}


/* ==========================================
   HABITS
========================================== */

$sql = "
    SELECT current_streak
    FROM habits
    WHERE user_id = ?
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $userId);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $habitCount++;

        $streak = (int)$row["current_streak"];

        if ($streak > $bestStreak) {

            $bestStreak = $streak;

        }

    }

    $stmt->close();

}


/* ==========================================
   FINANCE
========================================== */

$sql = "
    SELECT type, amount
    FROM transactions
    WHERE user_id = ?
";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $userId);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $type = strtolower(trim($row["type"]));

        $amount = (float)$row["amount"];


        if ($type === "income") {

            $income += $amount;

        }


        if ($type === "expense") {

            $expense += $amount;

        }

    }

    $stmt->close();

}


$balance = $income - $expense;


/* ==========================================
   LEARNING
========================================== */

/*
   We don't depend on a fixed column name here.
   We only check whether courses table exists.
*/

$checkCourses = $conn->query("
    SHOW TABLES LIKE 'courses'
");

if (
    $checkCourses &&
    $checkCourses->num_rows > 0
) {

    $courseResult = $conn->query("
        SELECT COUNT(*) AS total
        FROM courses
        WHERE user_id = $userId
    ");

    if ($courseResult) {

        $courseRow =
            $courseResult->fetch_assoc();

        $courseCount =
            (int)$courseRow["total"];

    }

}


/* ==========================================
   AI RESPONSE
========================================== */

$response = "";


/* ==========================================
   TODAY / FOCUS
========================================== */

if (
    strpos($q, "today") !== false ||
    strpos($q, "focus") !== false ||
    strpos($q, "priority") !== false
) {

    $response .=
        "🎯 <strong>Today's LifeOS Focus</strong><br><br>";


    if ($pendingTasks > 0) {

        $response .=
            "📋 You have <strong>"
            . $pendingTasks
            . "</strong> pending task(s).<br><br>";


        $response .=
            "👉 Start by completing your highest-priority pending task.<br><br>";

    } else {

        $response .=
            "🎉 You don't have any pending tasks right now.<br><br>";

    }


    if ($habitCount > 0) {

        $response .=
            "🔥 You have <strong>"
            . $habitCount
            . "</strong> habit(s) being tracked.<br>";

        $response .=
            "Your best current streak is <strong>"
            . $bestStreak
            . " days</strong>.<br><br>";

    }


    if ($totalGoals > 0) {

        $response .=
            "🎯 Your average goal progress is <strong>"
            . $goalProgress
            . "%</strong>.<br><br>";

    }


    $response .=
        "💡 <strong>Suggestion:</strong> Focus on one important task first, then maintain your habits.";

}


/* ==========================================
   GOALS
========================================== */

elseif (
    strpos($q, "goal") !== false
) {

    $response .=
        "🎯 <strong>Your Goals</strong><br><br>";


    if ($totalGoals === 0) {

        $response .=
            "You haven't added any goals yet.<br><br>";

        $response .=
            "Create your first goal from the Goals module.";

    } else {

        $response .=
            "You currently have <strong>"
            . $totalGoals
            . "</strong> goal(s).<br><br>";

        $response .=
            "📊 Average progress: <strong>"
            . $goalProgress
            . "%</strong><br><br>";


        if ($goalProgress >= 75) {

            $response .=
                "🌟 Excellent progress! Keep going.";

        } elseif ($goalProgress >= 40) {

            $response .=
                "👍 You're making good progress. Stay consistent.";

        } else {

            $response .=
                "💡 Your goals need more attention. Try working on one goal every day.";

        }

    }

}


/* ==========================================
   HABITS
========================================== */

elseif (
    strpos($q, "habit") !== false ||
    strpos($q, "streak") !== false
) {

    $response .=
        "🔥 <strong>Your Habit Overview</strong><br><br>";


    if ($habitCount === 0) {

        $response .=
            "You haven't added any habits yet.<br><br>";

        $response .=
            "Start with one simple daily habit.";

    } else {

        $response .=
            "You are currently tracking <strong>"
            . $habitCount
            . "</strong> habit(s).<br><br>";

        $response .=
            "🔥 Best current streak: <strong>"
            . $bestStreak
            . " days</strong><br><br>";


        if ($bestStreak >= 7) {

            $response .=
                "🌟 Great consistency! Keep your streak alive.";

        } elseif ($bestStreak > 0) {

            $response .=
                "💪 Keep going and try to reach a 7-day streak.";

        } else {

            $response .=
                "💡 Start completing your habits daily to build a streak.";

        }

    }

}


/* ==========================================
   FINANCE
========================================== */

elseif (
    strpos($q, "finance") !== false ||
    strpos($q, "money") !== false ||
    strpos($q, "expense") !== false ||
    strpos($q, "saving") !== false ||
    strpos($q, "spend") !== false
) {

    $response .=
        "💰 <strong>Finance Overview</strong><br><br>";


    $response .=
        "💵 Income: <strong>₹"
        . number_format($income, 2)
        . "</strong><br>";

    $response .=
        "💸 Expenses: <strong>₹"
        . number_format($expense, 2)
        . "</strong><br>";

    $response .=
        "💰 Balance: <strong>₹"
        . number_format($balance, 2)
        . "</strong><br><br>";


    if ($balance > 0) {

        $response .=
            "✅ Your recorded income is currently higher than your expenses.<br><br>";

        $response .=
            "💡 Try saving a fixed percentage of your available balance.";

    } elseif ($balance < 0) {

        $response .=
            "⚠️ Your recorded expenses are higher than your income.<br><br>";

        $response .=
            "Review unnecessary expenses and try to control spending.";

    } else {

        $response .=
            "💡 Add some income and expense records to get better financial insights.";

    }

}


/* ==========================================
   STUDY / LEARNING
========================================== */

elseif (
    strpos($q, "study") !== false ||
    strpos($q, "learn") !== false ||
    strpos($q, "course") !== false ||
    strpos($q, "exam") !== false
) {

    $response .=
        "📚 <strong>Learning Coach</strong><br><br>";


    if ($courseCount > 0) {

        $response .=
            "You have <strong>"
            . $courseCount
            . "</strong> course(s) recorded in LifeOS.<br><br>";

    } else {

        $response .=
            "No courses are currently recorded in LifeOS.<br><br>";

    }


    $response .=
        "📖 Suggested study routine:<br><br>";

    $response .=
        "1️⃣ 45 minutes focused study<br>";

    $response .=
        "2️⃣ 10 minutes break<br>";

    $response .=
        "3️⃣ 30 minutes practice<br>";

    $response .=
        "4️⃣ 15 minutes revision<br><br>";

    $response .=
        "💡 Consistent daily study is better than last-minute preparation.";

}


/* ==========================================
   CAREER
========================================== */

elseif (
    strpos($q, "career") !== false ||
    strpos($q, "job") !== false ||
    strpos($q, "skill") !== false ||
    strpos($q, "resume") !== false
) {

    $response .=
        "💼 <strong>Career Coach</strong><br><br>";

    $response .=
        "For your career growth, focus on three areas:<br><br>";

    $response .=
        "1️⃣ Improve one technical skill<br>";

    $response .=
        "2️⃣ Practice interview/aptitude questions<br>";

    $response .=
        "3️⃣ Keep your resume and projects updated<br><br>";

    $response .=
        "🚀 Small improvements every day create strong career progress.";

}


/* ==========================================
   SUMMARY
========================================== */

elseif (
    strpos($q, "summary") !== false ||
    strpos($q, "overview") !== false ||
    strpos($q, "my life") !== false
) {

    $response .=
        "🤖 <strong>Your LifeOS Summary</strong><br><br>";

    $response .=
        "🎯 Goal Progress: <strong>"
        . $goalProgress
        . "%</strong><br>";

    $response .=
        "📋 Pending Tasks: <strong>"
        . $pendingTasks
        . "</strong><br>";

    $response .=
        "🔥 Habits: <strong>"
        . $habitCount
        . "</strong><br>";

    $response .=
        "📚 Courses: <strong>"
        . $courseCount
        . "</strong><br>";

    $response .=
        "💰 Balance: <strong>₹"
        . number_format($balance, 2)
        . "</strong><br><br>";

    $response .=
        "💡 Focus on pending tasks, maintain your habits and continue your learning.";

}


/* ==========================================
   GENERAL
========================================== */

else {

    $response .=
        "🤖 <strong>LifeOS Copilot</strong><br><br>";

    $response .=
        "I can analyze your LifeOS information and give smart suggestions.<br><br>";

    $response .=
        "Try asking:<br><br>";

    $response .=
        "🎯 What should I focus on today?<br>";

    $response .=
        "🎯 How are my goals?<br>";

    $response .=
        "🔥 Show my habits<br>";

    $response .=
        "💰 How is my finance?<br>";

    $response .=
        "📚 Help me with my study<br>";

    $response .=
        "💼 Help me with my career<br>";

    $response .=
        "📊 Give me my life summary";

}


/* ==========================================
   FINAL JSON RESPONSE
========================================== */

sendResponse(
    true,
    $response
);

?>