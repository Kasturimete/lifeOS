<?php

session_start();

require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION["user_id"];


/* =========================
   ADD COURSE
========================= */

if (isset($_POST["add_course"])) {

    $courseName = $_POST["course_name"];
    $category = $_POST["category"];
    $progress = intval($_POST["progress"]);
    $startDate = $_POST["start_date"];
    $endDate = $_POST["end_date"];

    $status = ($progress >= 100) ? "Completed" : "In Progress";

    $stmt = $conn->prepare(
        "INSERT INTO courses
        (user_id, course_name, category, progress, start_date, end_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "ississs",
        $userId,
        $courseName,
        $category,
        $progress,
        $startDate,
        $endDate,
        $status
    );

    $stmt->execute();

    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   UPDATE PROGRESS
========================= */

if (isset($_POST["update_progress"])) {

    $courseId = intval($_POST["course_id"]);
    $progress = intval($_POST["progress"]);

    if ($progress < 0) {
        $progress = 0;
    }

    if ($progress > 100) {
        $progress = 100;
    }

    $status = ($progress >= 100) ? "Completed" : "In Progress";

    $stmt = $conn->prepare(
        "UPDATE courses
         SET progress = ?, status = ?
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param(
        "isii",
        $progress,
        $status,
        $courseId,
        $userId
    );

    $stmt->execute();

    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   ADD STUDY SESSION
========================= */

if (isset($_POST["add_session"])) {

    $courseId = intval($_POST["course_id"]);
    $studyDate = $_POST["study_date"];
    $duration = intval($_POST["duration"]);

    $stmt = $conn->prepare(
        "INSERT INTO study_sessions
        (user_id, course_id, study_date, duration)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "iisi",
        $userId,
        $courseId,
        $studyDate,
        $duration
    );

    $stmt->execute();

    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   DELETE COURSE
========================= */

if (isset($_GET["delete"])) {

    $courseId = intval($_GET["delete"]);

    $stmt = $conn->prepare(
        "DELETE FROM courses
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param(
        "ii",
        $courseId,
        $userId
    );

    $stmt->execute();

    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   GET COURSES
========================= */

$stmt = $conn->prepare(
    "SELECT *
     FROM courses
     WHERE user_id = ?
     ORDER BY id DESC"
);

$stmt->bind_param("i", $userId);

$stmt->execute();

$courses = $stmt->get_result();


/* =========================
   LEARNING STATISTICS
========================= */

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM courses
     WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$totalCourses = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM courses
     WHERE user_id = ?
     AND status = 'Completed'"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$completedCourses = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(duration),0) AS total
     FROM study_sessions
     WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$totalStudyMinutes = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


$averageProgress = 0;

if ($totalCourses > 0) {

    $stmt = $conn->prepare(
        "SELECT AVG(progress) AS avg_progress
         FROM courses
         WHERE user_id = ?"
    );

    $stmt->bind_param("i", $userId);

    $stmt->execute();

    $averageProgress =
        round($stmt->get_result()->fetch_assoc()["avg_progress"]);

    $stmt->close();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Learning | LifeOS</title>

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
    max-width: 1200px;
    margin: auto;
    padding: 30px 0;
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
    text-decoration: none;
    color: white;
    background: #211b3d;
    padding: 10px 18px;
    border-radius: 10px;
}


/* TITLE */

h1 {
    font-size: 32px;
    margin-bottom: 8px;
}

.subtitle {
    color: #aaa4c0;
    margin-bottom: 25px;
}


/* STATS */

.stats {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #17132d;
    border: 1px solid #2c2550;
    border-radius: 18px;
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


/* MAIN GRID */

.main-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 25px;
}


/* FORM */

.card {
    background: #17132d;
    border: 1px solid #2c2550;
    border-radius: 18px;
    padding: 25px;
}

.card h2 {
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    color: #aaa4c0;
    font-size: 14px;
    margin-bottom: 7px;
}

input,
select {
    width: 100%;
    padding: 12px;
    background: #0e0b20;
    color: white;
    border: 1px solid #39315e;
    border-radius: 10px;
}

button {
    width: 100%;
    border: none;
    padding: 13px;
    border-radius: 10px;
    background: linear-gradient(135deg,#8f5cff,#c06cff);
    color: white;
    font-weight: bold;
    cursor: pointer;
}


/* COURSE */

.course {
    padding: 18px 0;
    border-bottom: 1px solid #2c2550;
}

.course:last-child {
    border-bottom: none;
}

.course-top {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.course-name {
    font-size: 18px;
    font-weight: bold;
}

.category {
    color: #aaa4c0;
    font-size: 13px;
}

.progress-bg {
    width: 100%;
    height: 9px;
    background: #2b2545;
    border-radius: 10px;
    margin: 12px 0;
}

.progress {
    height: 100%;
    background: linear-gradient(90deg,#8f5cff,#c06cff);
    border-radius: 10px;
}

.progress-text {
    color: #aaa4c0;
    font-size: 13px;
}

.course-actions {
    margin-top: 12px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.course-actions form {
    display: flex;
    gap: 8px;
}

.course-actions input {
    width: 80px;
}

.small-btn {
    width: auto;
    padding: 9px 12px;
}

.delete {
    background: #391c31;
    color: #ff6b8a;
    text-decoration: none;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 13px;
}

.empty {
    text-align: center;
    color: #aaa4c0;
    padding: 30px;
}


/* STUDY */

.study-card {
    margin-top: 25px;
}

.study-form {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 12px;
}

.study-form button {
    width: auto;
}


/* RESPONSIVE */

@media(max-width:800px) {

    .stats {
        grid-template-columns: 1fr;
    }

    .main-grid {
        grid-template-columns: 1fr;
    }

    .study-form {
        grid-template-columns: 1fr;
    }

    .study-form button {
        width: 100%;
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

    <a href="../dashboard.php" class="back">
        ← Dashboard
    </a>

</div>


<h1>Learning 📚</h1>

<p class="subtitle">
    Manage your courses, track your progress and record your study time.
</p>


<!-- STATISTICS -->

<div class="stats">

    <div class="stat-card">

        <h3>Total Courses</h3>

        <div class="stat-number">
            <?php echo $totalCourses; ?>
        </div>

    </div>


    <div class="stat-card">

        <h3>Completed Courses</h3>

        <div class="stat-number">
            <?php echo $completedCourses; ?>
        </div>

    </div>


    <div class="stat-card">

        <h3>Average Progress</h3>

        <div class="stat-number">
            <?php echo $averageProgress; ?>%
        </div>

    </div>

</div>


<!-- MAIN -->

<div class="main-grid">


<!-- ADD COURSE -->

<div class="card">

    <h2>➕ Add Course</h2>

    <form method="POST">

        <div class="form-group">

            <label>Course Name</label>

            <input
                type="text"
                name="course_name"
                placeholder="Example: Java Programming"
                required
            >

        </div>


        <div class="form-group">

            <label>Category</label>

            <select name="category" required>

                <option value="">
                    Select Category
                </option>

                <option>Programming</option>

                <option>Database</option>

                <option>Web Development</option>

                <option>Data Science</option>

                <option>AI / ML</option>

                <option>Cloud</option>

                <option>Aptitude</option>

                <option>Other</option>

            </select>

        </div>


        <div class="form-group">

            <label>Current Progress (%)</label>

            <input
                type="number"
                name="progress"
                min="0"
                max="100"
                value="0"
                required
            >

        </div>


        <div class="form-group">

            <label>Start Date</label>

            <input
                type="date"
                name="start_date"
                required
            >

        </div>


        <div class="form-group">

            <label>End Date</label>

            <input
                type="date"
                name="end_date"
                required
            >

        </div>


        <button
            type="submit"
            name="add_course"
        >

            Add Course

        </button>

    </form>

</div>


<!-- COURSE LIST -->

<div class="card">

    <h2>📖 My Courses</h2>


    <?php if ($courses->num_rows > 0): ?>

        <?php while ($course = $courses->fetch_assoc()): ?>


            <div class="course">

                <div class="course-top">

                    <div>

                        <div class="course-name">

                            <?php
                            echo htmlspecialchars(
                                $course["course_name"]
                            );
                            ?>

                        </div>

                        <div class="category">

                            <?php
                            echo htmlspecialchars(
                                $course["category"]
                            );
                            ?>

                            •

                            <?php
                            echo htmlspecialchars(
                                $course["status"]
                            );
                            ?>

                        </div>

                    </div>


                    <div>

                        <?php echo $course["progress"]; ?>%

                    </div>

                </div>


                <!-- PROGRESS BAR -->

                <div class="progress-bg">

                    <div
                        class="progress"
                        style="width: <?php echo $course["progress"]; ?>%;"
                    ></div>

                </div>


                <div class="progress-text">

                    <?php echo $course["start_date"]; ?>

                    →

                    <?php echo $course["end_date"]; ?>

                </div>


                <!-- ACTIONS -->

                <div class="course-actions">


                    <form method="POST">

                        <input
                            type="hidden"
                            name="course_id"
                            value="<?php echo $course["id"]; ?>"
                        >

                        <input
                            type="number"
                            name="progress"
                            min="0"
                            max="100"
                            value="<?php echo $course["progress"]; ?>"
                        >

                        <button
                            class="small-btn"
                            type="submit"
                            name="update_progress"
                        >

                            Update

                        </button>

                    </form>


                    <a
                        class="delete"
                        href="?delete=<?php echo $course["id"]; ?>"
                        onclick="return confirm('Delete this course?');"
                    >

                        Delete

                    </a>

                </div>

            </div>


        <?php endwhile; ?>


    <?php else: ?>

        <div class="empty">

            No courses added yet.

            <br>

            Add your first course from the left.

        </div>

    <?php endif; ?>


</div>

</div>


<!-- STUDY SESSION -->

<div class="card study-card">

    <h2>⏱️ Add Study Session</h2>

    <form
        method="POST"
        class="study-form"
    >


        <select name="course_id" required>

            <option value="">
                Select Course
            </option>

            <?php

            $courseList = $conn->query(
                "SELECT id, course_name
                 FROM courses
                 WHERE user_id = $userId
                 ORDER BY course_name"
            );

            while ($course = $courseList->fetch_assoc()):

            ?>

                <option value="<?php echo $course["id"]; ?>">

                    <?php
                    echo htmlspecialchars(
                        $course["course_name"]
                    );
                    ?>

                </option>

            <?php endwhile; ?>

        </select>


        <input
            type="date"
            name="study_date"
            value="<?php echo date('Y-m-d'); ?>"
            required
        >


        <input
            type="number"
            name="duration"
            min="1"
            placeholder="Minutes"
            required
        >


        <button
            type="submit"
            name="add_session"
        >

            + Add Session

        </button>


    </form>

</div>


</div>

</body>

</html>