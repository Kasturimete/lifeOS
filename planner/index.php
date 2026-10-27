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
   ADD TASK
========================= */

if (isset($_POST["add_task"])) {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $priority = $_POST["priority"];
    $due_date = $_POST["due_date"];

    $stmt = $conn->prepare(
        "INSERT INTO tasks
        (user_id, title, description, priority, due_date, status)
        VALUES (?, ?, ?, ?, ?, 'Pending')"
    );

    $stmt->bind_param(
        "issss",
        $userId,
        $title,
        $description,
        $priority,
        $due_date
    );

    if ($stmt->execute()) {
        $message = "Task added successfully!";
    }
}


/* =========================
   MARK TASK COMPLETE
========================= */

if (isset($_GET["complete"])) {

    $taskId = intval($_GET["complete"]);

    $stmt = $conn->prepare(
        "UPDATE tasks
         SET status = 'Completed'
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param("ii", $taskId, $userId);
    $stmt->execute();

    header("Location: index.php");
    exit();
}


/* =========================
   DELETE TASK
========================= */

if (isset($_GET["delete"])) {

    $taskId = intval($_GET["delete"]);

    $stmt = $conn->prepare(
        "DELETE FROM tasks
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param("ii", $taskId, $userId);
    $stmt->execute();

    header("Location: index.php");
    exit();
}


/* =========================
   GET TASKS
========================= */

$stmt = $conn->prepare(
    "SELECT *
     FROM tasks
     WHERE user_id = ?
     ORDER BY
        CASE WHEN status = 'Pending' THEN 0 ELSE 1 END,
        due_date ASC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$tasks = $stmt->get_result();


/* =========================
   TASK COUNTS
========================= */

$stmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'Completed') AS completed,
        SUM(status = 'Pending') AS pending
     FROM tasks
     WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$counts = $stmt->get_result()->fetch_assoc();

$totalTasks = $counts["total"] ?? 0;
$completedTasks = $counts["completed"] ?? 0;
$pendingTasks = $counts["pending"] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Planner | LifeOS</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background:
        radial-gradient(circle at top left, #32146b, transparent 30%),
        radial-gradient(circle at bottom right, #4a155f, transparent 30%),
        #090615;
    color: white;
}

.planner-page {
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
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 30px;
}

.stat-card {
    padding: 22px;
    border-radius: 18px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
}

.stat-card h3 {
    margin: 0;
    color: #aaa;
    font-size: 14px;
}

.stat-number {
    font-size: 30px;
    font-weight: bold;
    margin-top: 10px;
}


/* FORM */

.task-form {
    padding: 25px;
    border-radius: 20px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    margin-bottom: 30px;
}

.task-form h2 {
    margin-top: 0;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
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


/* MESSAGE */

.message {
    padding: 12px;
    border-radius: 10px;
    background: rgba(34,197,94,.15);
    color: #86efac;
    margin-bottom: 20px;
}


/* TASK LIST */

.tasks {
    display: grid;
    gap: 15px;
}

.task {
    padding: 20px;
    border-radius: 18px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    transition: .3s;
}

.task:hover {
    transform: translateY(-3px);
    border-color: #8b5cf6;
}

.task.completed {
    opacity: .6;
}

.task-top {
    display: flex;
    justify-content: space-between;
    gap: 15px;
}

.task h3 {
    margin: 0;
}

.task-description {
    color: #999;
    margin: 10px 0;
}

.priority {
    padding: 5px 10px;
    border-radius: 20px;
    background: rgba(168,85,247,.15);
    color: #c4b5fd;
    font-size: 12px;
}

.task-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    color: #999;
    font-size: 13px;
}

.actions a {
    margin-left: 12px;
    text-decoration: none;
}

.complete {
    color: #86efac;
}

.delete {
    color: #fca5a5;
}

.empty {
    padding: 50px;
    text-align: center;
    color: #888;
    background: rgba(255,255,255,.04);
    border-radius: 20px;
}


/* MOBILE */

@media(max-width:700px) {

    .stats {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .task-top {
        flex-direction: column;
    }

    .task-bottom {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

}

</style>

</head>


<body>

<div class="planner-page">

    <h1 class="page-title">
        📅 Daily Planner
    </h1>

    <p class="subtitle">
        Plan your day. Focus on what matters.
    </p>


    <!-- STATS -->

    <div class="stats">

        <div class="stat-card">

            <h3>Total Tasks</h3>

            <div class="stat-number">
                <?php echo $totalTasks; ?>
            </div>

        </div>


        <div class="stat-card">

            <h3>Pending</h3>

            <div class="stat-number">
                <?php echo $pendingTasks; ?>
            </div>

        </div>


        <div class="stat-card">

            <h3>Completed</h3>

            <div class="stat-number">
                <?php echo $completedTasks; ?>
            </div>

        </div>

    </div>


    <?php if ($message != ""): ?>

        <div class="message">
            <?php echo $message; ?>
        </div>

    <?php endif; ?>


    <!-- ADD TASK -->

    <div class="task-form">

        <h2>✨ Add Today's Task</h2>

        <form method="POST">

            <div class="form-grid">

                <input
                    type="text"
                    name="title"
                    placeholder="Task title"
                    required
                >

                <select name="priority">

                    <option value="Low">
                        Low Priority
                    </option>

                    <option value="Medium" selected>
                        Medium Priority
                    </option>

                    <option value="High">
                        High Priority
                    </option>

                </select>


                <textarea
                    name="description"
                    class="full"
                    placeholder="Task description..."
                ></textarea>


                <input
                    type="date"
                    name="due_date"
                    required
                >

            </div>


            <button
                type="submit"
                name="add_task"
                class="add-btn"
            >
                + Add Task
            </button>

        </form>

    </div>


    <!-- TASKS -->

    <h2>Today's Focus</h2>

    <br>


    <?php if ($tasks->num_rows > 0): ?>

        <div class="tasks">

        <?php while ($task = $tasks->fetch_assoc()): ?>

            <div class="task
                <?php
                echo $task["status"] == "Completed"
                    ? "completed"
                    : "";
                ?>">

                <div class="task-top">

                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $task["title"]
                        );
                        ?>
                    </h3>

                    <span class="priority">
                        <?php
                        echo htmlspecialchars(
                            $task["priority"]
                        );
                        ?>
                    </span>

                </div>


                <p class="task-description">

                    <?php
                    echo htmlspecialchars(
                        $task["description"]
                    );
                    ?>

                </p>


                <div class="task-bottom">

                    <span>
                        📅
                        <?php
                        echo date(
                            "d M Y",
                            strtotime(
                                $task["due_date"]
                            )
                        );
                        ?>
                    </span>


                    <span>

                        <?php
                        if ($task["status"] == "Completed") {
                            echo "✅ Completed";
                        } else {
                            echo "⏳ Pending";
                        }
                        ?>

                    </span>


                    <div class="actions">

                        <?php if ($task["status"] != "Completed"): ?>

                            <a
                                class="complete"
                                href="?complete=<?php echo $task["id"]; ?>"
                            >
                                ✓ Complete
                            </a>

                        <?php endif; ?>


                        <a
                            class="delete"
                            href="?delete=<?php echo $task["id"]; ?>"
                            onclick="return confirm('Delete this task?');"
                        >
                            🗑 Delete
                        </a>

                    </div>

                </div>

            </div>

        <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty">

            <h2>🌱 No tasks yet</h2>

            <p>
                Add your first task and start your day.
            </p>

        </div>

    <?php endif; ?>

</div>

</body>
</html>