<?php

session_start();

require_once "../config/db.php";

/* Protect page */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION["user_id"];
$message = "";


/* =========================
   ADD GOAL
========================= */

if (isset($_POST["add_goal"])) {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $category = $_POST["category"];
    $priority = $_POST["priority"];
    $target_date = $_POST["target_date"];
    $progress = intval($_POST["progress"]);

    if ($progress < 0) {
        $progress = 0;
    }

    if ($progress > 100) {
        $progress = 100;
    }

    $status = ($progress == 100) ? "Completed" : "Pending";

    $stmt = $conn->prepare(
        "INSERT INTO goals
        (user_id, title, description, category, priority, target_date, progress, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "isssssis",
        $userId,
        $title,
        $description,
        $category,
        $priority,
        $target_date,
        $progress,
        $status
    );

    if ($stmt->execute()) {
        $message = "Goal added successfully!";
    }
}


/* =========================
   DELETE GOAL
========================= */

if (isset($_GET["delete"])) {

    $goalId = intval($_GET["delete"]);

    $stmt = $conn->prepare(
        "DELETE FROM goals WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param("ii", $goalId, $userId);
    $stmt->execute();

    header("Location: index.php");
    exit();
}


/* =========================
   GET GOALS
========================= */

$stmt = $conn->prepare(
    "SELECT * FROM goals
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$goals = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Goals | LifeOS</title>

    <link rel="stylesheet"
          href="../assets/css/dashboard.css">

    <style>

        body {
            background: #090615;
            color: white;
            font-family: Arial, sans-serif;
        }

        .goals-page {
            padding: 40px;
            margin-left: 260px;
        }

        .page-title {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: #999;
            margin-bottom: 30px;
        }

        .goal-form {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
        }

        .goal-form h2 {
            margin-top: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 13px;
            border-radius: 10px;
            border: 1px solid #333;
            background: #151020;
            color: white;
            outline: none;
        }

        textarea {
            min-height: 80px;
            resize: vertical;
        }

        .full {
            grid-column: 1 / -1;
        }

        .add-btn {
            margin-top: 15px;
            padding: 13px 22px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg,#7c3aed,#a855f7);
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .message {
            background: rgba(34,197,94,.15);
            color: #86efac;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .goals-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .goal-card {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 20px;
            padding: 22px;
            transition: .3s;
        }

        .goal-card:hover {
            transform: translateY(-5px);
            border-color: #8b5cf6;
            box-shadow: 0 15px 35px rgba(124,58,237,.2);
        }

        .goal-card h3 {
            margin-top: 0;
        }

        .goal-description {
            color: #aaa;
            min-height: 40px;
        }

        .goal-info {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            font-size: 13px;
            color: #bbb;
        }

        .priority {
            padding: 5px 9px;
            border-radius: 20px;
            background: rgba(168,85,247,.15);
            color: #c4b5fd;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #24202e;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: linear-gradient(90deg,#7c3aed,#c084fc);
            border-radius: 10px;
        }

        .progress-text {
            margin-top: 8px;
            font-size: 13px;
            color: #aaa;
        }

        .delete-btn {
            display: inline-block;
            margin-top: 15px;
            color: #fca5a5;
            text-decoration: none;
            font-size: 13px;
        }

        .empty {
            padding: 50px;
            text-align: center;
            color: #888;
            background: rgba(255,255,255,.04);
            border-radius: 20px;
        }

        @media(max-width: 900px) {

            .goals-page {
                margin-left: 0;
                padding: 20px;
            }

            .goals-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>


<div class="goals-page">

    <h1 class="page-title">🎯 My Goals</h1>

    <p class="page-subtitle">
        Turn your plans into achievements.
    </p>


    <?php if ($message != ""): ?>

        <div class="message">
            <?php echo $message; ?>
        </div>

    <?php endif; ?>


    <!-- ADD GOAL -->

    <div class="goal-form">

        <h2>✨ Create New Goal</h2>

        <form method="POST">

            <div class="form-grid">

                <input
                    type="text"
                    name="title"
                    placeholder="Goal title e.g. Learn Java"
                    required
                >

                <select name="category" required>

                    <option value="">Select Category</option>

                    <option value="Career">Career</option>
                    <option value="Education">Education</option>
                    <option value="Health">Health</option>
                    <option value="Finance">Finance</option>
                    <option value="Personal">Personal</option>

                </select>


                <textarea
                    name="description"
                    class="full"
                    placeholder="Describe your goal..."
                ></textarea>


                <select name="priority">

                    <option value="Low">Low Priority</option>
                    <option value="Medium" selected>
                        Medium Priority
                    </option>
                    <option value="High">High Priority</option>

                </select>


                <input
                    type="date"
                    name="target_date"
                >


                <div>

                    <label>Progress (%)</label>

                    <input
                        type="number"
                        name="progress"
                        min="0"
                        max="100"
                        value="0"
                    >

                </div>

            </div>


            <button
                type="submit"
                name="add_goal"
                class="add-btn"
            >
                + Add Goal
            </button>

        </form>

    </div>


    <!-- GOALS -->

    <h2>My Goals</h2>

    <br>


    <?php if ($goals->num_rows > 0): ?>

        <div class="goals-grid">

            <?php while ($goal = $goals->fetch_assoc()): ?>

                <div class="goal-card">

                    <h3>
                        <?php echo htmlspecialchars($goal["title"]); ?>
                    </h3>


                    <p class="goal-description">

                        <?php
                        echo htmlspecialchars(
                            $goal["description"]
                        );
                        ?>

                    </p>


                    <div class="goal-info">

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $goal["category"]
                            );
                            ?>
                        </span>

                        <span class="priority">

                            <?php
                            echo htmlspecialchars(
                                $goal["priority"]
                            );
                            ?>

                        </span>

                    </div>


                    <div class="progress-bar">

                        <div
                            class="progress"
                            style="width:
                            <?php echo $goal["progress"]; ?>%"
                        >
                        </div>

                    </div>


                    <div class="progress-text">

                        <?php echo $goal["progress"]; ?>%
                        completed

                    </div>


                    <div class="goal-info">

                        <span>
                            📅
                            <?php
                            echo $goal["target_date"]
                                ? date(
                                    "d M Y",
                                    strtotime(
                                        $goal["target_date"]
                                    )
                                )
                                : "No deadline";
                            ?>
                        </span>

                        <span>
                            <?php echo $goal["status"]; ?>
                        </span>

                    </div>


                    <a
                        href="index.php?delete=<?php echo $goal["id"]; ?>"
                        class="delete-btn"
                        onclick="return confirm('Delete this goal?');"
                    >
                        🗑 Delete
                    </a>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty">

            <h2>🌱 No goals yet</h2>

            <p>
                Create your first goal and start building your future.
            </p>

        </div>

    <?php endif; ?>


</div>

</body>
</html>