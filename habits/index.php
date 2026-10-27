<?php

session_start();

require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION["user_id"];

$message = "";


/* =========================
   ADD HABIT
========================= */

if (isset($_POST["add_habit"])) {

    $name = trim($_POST["name"]);
    $frequency = $_POST["frequency"];

    if (!empty($name)) {

        $stmt = $conn->prepare(
            "INSERT INTO habits
            (user_id, name, frequency)
            VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "iss",
            $userId,
            $name,
            $frequency
        );

        if ($stmt->execute()) {
            $message = "Habit added successfully!";
        }
    }
}


/* =========================
   COMPLETE TODAY'S HABIT
========================= */

if (isset($_GET["complete"])) {

    $habitId = intval($_GET["complete"]);

    $today = date("Y-m-d");

    /* Check whether already completed */

    $check = $conn->prepare(
        "SELECT id
         FROM habit_logs
         WHERE habit_id = ?
         AND user_id = ?
         AND completed_date = ?"
    );

    $check->bind_param(
        "iis",
        $habitId,
        $userId,
        $today
    );

    $check->execute();

    $result = $check->get_result();

    if ($result->num_rows == 0) {

        $stmt = $conn->prepare(
            "INSERT INTO habit_logs
            (habit_id, user_id, completed_date)
            VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "iis",
            $habitId,
            $userId,
            $today
        );

        $stmt->execute();

        /* Increase streak */

        $stmt = $conn->prepare(
            "UPDATE habits
             SET current_streak = current_streak + 1
             WHERE id = ? AND user_id = ?"
        );

        $stmt->bind_param(
            "ii",
            $habitId,
            $userId
        );

        $stmt->execute();
    }

    header("Location: index.php");
    exit();
}


/* =========================
   DELETE HABIT
========================= */

if (isset($_GET["delete"])) {

    $habitId = intval($_GET["delete"]);

    $stmt = $conn->prepare(
        "DELETE FROM habits
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param(
        "ii",
        $habitId,
        $userId
    );

    $stmt->execute();

    header("Location: index.php");
    exit();
}


/* =========================
   GET HABITS
========================= */

$stmt = $conn->prepare(
    "SELECT *
     FROM habits
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$habits = $stmt->get_result();


/* =========================
   TODAY COMPLETION COUNT
========================= */

$today = date("Y-m-d");

$stmt = $conn->prepare(
    "SELECT COUNT(*)
     AS completed
     FROM habit_logs
     WHERE user_id = ?
     AND completed_date = ?"
);

$stmt->bind_param(
    "is",
    $userId,
    $today
);

$stmt->execute();

$todayResult = $stmt->get_result()->fetch_assoc();

$todayCompleted = $todayResult["completed"] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Habits | LifeOS</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family: Arial, sans-serif;

    color: white;

    background:
        radial-gradient(
            circle at top left,
            #32146b,
            transparent 30%
        ),
        radial-gradient(
            circle at bottom right,
            #4a155f,
            transparent 30%
        ),
        #090615;
}


/* PAGE */

.habits-page {

    max-width: 1200px;

    margin: auto;

    padding: 40px 25px;
}


/* HEADER */

.page-title {

    font-size: 34px;

    margin-bottom: 8px;
}

.subtitle {

    color: #999;

    margin-bottom: 30px;
}


/* STATS */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 30px;
}

.stat {

    padding: 25px;

    border-radius: 18px;

    background:
        rgba(255,255,255,.06);

    border:
        1px solid rgba(255,255,255,.1);
}

.stat h3 {

    margin: 0;

    color: #aaa;

    font-size: 14px;
}

.number {

    font-size: 32px;

    font-weight: bold;

    margin-top: 10px;
}


/* FORM */

.add-box {

    padding: 25px;

    border-radius: 20px;

    background:
        rgba(255,255,255,.06);

    border:
        1px solid rgba(255,255,255,.1);

    margin-bottom: 30px;
}

.form-row {

    display: grid;

    grid-template-columns:
        2fr 1fr auto;

    gap: 15px;
}

input,
select {

    width: 100%;

    padding: 13px;

    border-radius: 10px;

    border:
        1px solid #333;

    background: #151020;

    color: white;

    outline: none;
}

button {

    padding: 13px 20px;

    border: none;

    border-radius: 10px;

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #a855f7
        );

    color: white;

    font-weight: bold;

    cursor: pointer;
}


/* MESSAGE */

.message {

    padding: 12px;

    margin-bottom: 20px;

    border-radius: 10px;

    background:
        rgba(34,197,94,.15);

    color: #86efac;
}


/* HABITS */

.habits-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;
}

.habit {

    padding: 25px;

    border-radius: 20px;

    background:
        rgba(255,255,255,.06);

    border:
        1px solid rgba(255,255,255,.1);

    transition: .3s;
}

.habit:hover {

    transform:
        translateY(-5px);

    border-color:
        #8b5cf6;

    box-shadow:
        0 15px 35px
        rgba(124,58,237,.2);
}

.habit-icon {

    font-size: 35px;

    margin-bottom: 10px;
}

.habit h3 {

    margin: 5px 0;
}

.frequency {

    color: #999;

    font-size: 13px;
}


/* STREAK */

.streak {

    margin: 20px 0;

    padding: 15px;

    border-radius: 12px;

    background:
        rgba(168,85,247,.1);
}

.streak-number {

    font-size: 30px;

    font-weight: bold;

    color: #c4b5fd;
}


/* ACTIONS */

.complete-btn {

    display: block;

    text-align: center;

    padding: 12px;

    border-radius: 10px;

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #a855f7
        );

    color: white;

    text-decoration: none;

    font-weight: bold;
}

.delete {

    display: block;

    text-align: center;

    margin-top: 12px;

    color: #fca5a5;

    text-decoration: none;

    font-size: 13px;
}

.done {

    background:
        rgba(34,197,94,.15);

    color: #86efac;
}


/* EMPTY */

.empty {

    padding: 50px;

    text-align: center;

    color: #888;

    border-radius: 20px;

    background:
        rgba(255,255,255,.04);
}


/* MOBILE */

@media(max-width:800px) {

    .stats {

        grid-template-columns: 1fr;
    }

    .habits-grid {

        grid-template-columns: 1fr;
    }

    .form-row {

        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>

<div class="habits-page">


    <h1 class="page-title">
         Habit Tracker
    </h1>

    <p class="subtitle">
        Small habits. Big changes.
    </p>


    <!-- STATS -->

    <div class="stats">

        <div class="stat">

            <h3>
                Active Habits
            </h3>

            <div class="number">

                <?php
                echo $habits->num_rows;
                ?>

            </div>

        </div>


        <div class="stat">

            <h3>
                Completed Today
            </h3>

            <div class="number">

                <?php
                echo $todayCompleted;
                ?>

            </div>

        </div>


        <div class="stat">

            <h3>
                Today's Date
            </h3>

            <div class="number"
                 style="font-size:20px">

                <?php
                echo date("d M Y");
                ?>

            </div>

        </div>

    </div>


    <?php if ($message != ""): ?>

        <div class="message">

            <?php
            echo $message;
            ?>

        </div>

    <?php endif; ?>


    <!-- ADD HABIT -->

    <div class="add-box">

        <h2>
             Add New Habit
        </h2>

        <form method="POST">

            <div class="form-row">

                <input
                    type="text"
                    name="name"
                    placeholder="Example: Exercise 30 minutes"
                    required
                >


                <select name="frequency">

                    <option value="Daily">
                        Daily
                    </option>

                    <option value="Weekly">
                        Weekly
                    </option>

                </select>


                <button
                    type="submit"
                    name="add_habit"
                >
                    + Add
                </button>

            </div>

        </form>

    </div>


    <!-- HABITS -->

    <h2>
        My Habits
    </h2>

    <br>


    <?php if ($habits->num_rows > 0): ?>

        <div class="habits-grid">

            <?php while ($habit = $habits->fetch_assoc()): ?>


                <?php

                /* Check today's completion */

                $check = $conn->prepare(
                    "SELECT id
                     FROM habit_logs
                     WHERE habit_id = ?
                     AND user_id = ?
                     AND completed_date = ?"
                );

                $check->bind_param(
                    "iis",
                    $habit["id"],
                    $userId,
                    $today
                );

                $check->execute();

                $completedToday =
                    $check->get_result()->num_rows > 0;

                ?>


                <div class="habit">


                    <div class="habit-icon">

                        

                    </div>


                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $habit["name"]
                        );
                        ?>

                    </h3>


                    <div class="frequency">

                        <?php
                        echo htmlspecialchars(
                            $habit["frequency"]
                        );
                        ?>

                    </div>


                    <div class="streak">

                        <div>
                            Current Streak
                        </div>

                        <div class="streak-number">

                            
                            <?php
                            echo $habit["current_streak"];
                            ?>

                            days

                        </div>

                    </div>


                    <?php if ($completedToday): ?>

                        <div
                            class="complete-btn done"
                        >

                            Completed Today

                        </div>

                    <?php else: ?>

                        <a
                            href="?complete=<?php
                            echo $habit["id"];
                            ?>"
                            class="complete-btn"
                        >

                            ✓ Complete Today

                        </a>

                    <?php endif; ?>


                    <a
                        href="?delete=<?php
                        echo $habit["id"];
                        ?>"
                        class="delete"
                        onclick="
                        return confirm(
                            'Delete this habit?'
                        );
                        "
                    >

                        🗑 Delete

                    </a>

                </div>


            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty">

            <h2>
                 No habits yet
            </h2>

            <p>
                Add your first habit and build your streak.
            </p>

        </div>

    <?php endif; ?>


</div>

</body>

</html>