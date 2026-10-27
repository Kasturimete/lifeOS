<?php

session_start();

require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION["user_id"];


/* =========================
   ADD SKILL
========================= */

if (isset($_POST["add_skill"])) {

    $skillName = $_POST["skill_name"];
    $category = $_POST["category"];
    $progress = intval($_POST["progress"]);

    if ($progress < 0) {
        $progress = 0;
    }

    if ($progress > 100) {
        $progress = 100;
    }

    $stmt = $conn->prepare(
        "INSERT INTO skills
        (user_id, skill_name, category, progress)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "issi",
        $userId,
        $skillName,
        $category,
        $progress
    );

    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   UPDATE SKILL
========================= */

if (isset($_POST["update_skill"])) {

    $skillId = intval($_POST["skill_id"]);
    $progress = intval($_POST["progress"]);

    if ($progress < 0) {
        $progress = 0;
    }

    if ($progress > 100) {
        $progress = 100;
    }

    $stmt = $conn->prepare(
        "UPDATE skills
         SET progress = ?
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param(
        "iii",
        $progress,
        $skillId,
        $userId
    );

    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   DELETE SKILL
========================= */

if (isset($_GET["delete_skill"])) {

    $skillId = intval($_GET["delete_skill"]);

    $stmt = $conn->prepare(
        "DELETE FROM skills
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param(
        "ii",
        $skillId,
        $userId
    );

    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   ADD JOB APPLICATION
========================= */

if (isset($_POST["add_job"])) {

    $company = $_POST["company_name"];
    $role = $_POST["job_role"];
    $date = $_POST["application_date"];
    $status = $_POST["status"];
    $notes = $_POST["notes"];

    $stmt = $conn->prepare(
        "INSERT INTO job_applications
        (user_id, company_name, job_role, application_date, status, notes)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "isssss",
        $userId,
        $company,
        $role,
        $date,
        $status,
        $notes
    );

    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   UPDATE JOB STATUS
========================= */

if (isset($_POST["update_job"])) {

    $jobId = intval($_POST["job_id"]);
    $status = $_POST["status"];

    $stmt = $conn->prepare(
        "UPDATE job_applications
         SET status = ?
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param(
        "sii",
        $status,
        $jobId,
        $userId
    );

    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   DELETE JOB
========================= */

if (isset($_GET["delete_job"])) {

    $jobId = intval($_GET["delete_job"]);

    $stmt = $conn->prepare(
        "DELETE FROM job_applications
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param(
        "ii",
        $jobId,
        $userId
    );

    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   GET SKILLS
========================= */

$stmt = $conn->prepare(
    "SELECT *
     FROM skills
     WHERE user_id = ?
     ORDER BY id DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$skills = $stmt->get_result();


/* =========================
   GET JOBS
========================= */

$stmt = $conn->prepare(
    "SELECT *
     FROM job_applications
     WHERE user_id = ?
     ORDER BY application_date DESC, id DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$jobs = $stmt->get_result();


/* =========================
   STATISTICS
========================= */

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM skills
     WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$totalSkills = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


$stmt = $conn->prepare(
    "SELECT AVG(progress) AS avg_progress
     FROM skills
     WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$avgResult = $stmt->get_result()->fetch_assoc();

$averageSkill = $avgResult["avg_progress"]
    ? round($avgResult["avg_progress"])
    : 0;

$stmt->close();


$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM job_applications
     WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$totalJobs = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();


$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM job_applications
     WHERE user_id = ?
     AND status = 'Selected'"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$selectedJobs = $stmt->get_result()->fetch_assoc()["total"];

$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Career | LifeOS</title>

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
    font-size: 32px;
    margin-bottom: 8px;
}

.subtitle {
    color: #aaa4c0;
    margin-bottom: 25px;
}


/* STATISTICS */

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
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


/* GRID */

.grid {
    display: grid;
    grid-template-columns: 1fr 1.4fr;
    gap: 25px;
}


/* CARD */

.card {
    background: #17132d;
    border: 1px solid #2c2550;
    border-radius: 18px;
    padding: 25px;
}

.card h2 {
    margin-bottom: 20px;
}


/* FORM */

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
select,
textarea {
    width: 100%;
    padding: 12px;
    background: #0e0b20;
    color: white;
    border: 1px solid #39315e;
    border-radius: 10px;
    outline: none;
}

textarea {
    resize: vertical;
}

button {
    border: none;
    padding: 12px 16px;
    border-radius: 10px;
    background: linear-gradient(135deg, #8f5cff, #c06cff);
    color: white;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    opacity: .9;
}


/* SKILL */

.skill {
    padding: 17px 0;
    border-bottom: 1px solid #2c2550;
}

.skill:last-child {
    border-bottom: none;
}

.skill-top {
    display: flex;
    justify-content: space-between;
    margin-bottom: 7px;
}

.skill-name {
    font-weight: bold;
}

.skill-category {
    color: #aaa4c0;
    font-size: 13px;
}

.progress-bg {
    width: 100%;
    height: 9px;
    background: #2b2545;
    border-radius: 10px;
    margin: 10px 0;
}

.progress {
    height: 100%;
    background: linear-gradient(90deg, #8f5cff, #c06cff);
    border-radius: 10px;
}

.skill-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-top: 10px;
}

.skill-actions input {
    width: 80px;
}

.small-btn {
    padding: 8px 12px;
}


/* JOBS */

.jobs-card {
    margin-top: 25px;
}

.job {
    padding: 18px 0;
    border-bottom: 1px solid #2c2550;
}

.job:last-child {
    border-bottom: none;
}

.job-top {
    display: flex;
    justify-content: space-between;
    gap: 20px;
}

.company {
    font-size: 18px;
    font-weight: bold;
}

.role {
    color: #aaa4c0;
    margin-top: 5px;
}

.job-info {
    color: #88829e;
    font-size: 13px;
    margin-top: 7px;
}

.status {
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 12px;
    background: #30275a;
    color: #cdbbff;
    height: fit-content;
}

.job-actions {
    display: flex;
    gap: 10px;
    margin-top: 12px;
    align-items: center;
}

.job-actions select {
    width: 150px;
}

.delete {
    text-decoration: none;
    background: #391c31;
    color: #ff6b8a;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 13px;
}

.empty {
    color: #aaa4c0;
    text-align: center;
    padding: 30px;
}


/* RESPONSIVE */

@media(max-width:900px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .grid {
        grid-template-columns: 1fr;
    }

}

@media(max-width:600px) {

    .stats {
        grid-template-columns: 1fr;
    }

    .job-top {
        flex-direction: column;
    }

    .job-actions {
        flex-wrap: wrap;
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


<h1>Career 💼</h1>

<p class="subtitle">
    Build your skills, track job applications and manage your career journey.
</p>


<!-- STATISTICS -->

<div class="stats">

    <div class="stat-card">

        <h3>Total Skills</h3>

        <div class="stat-number">
            <?php echo $totalSkills; ?>
        </div>

    </div>


    <div class="stat-card">

        <h3>Average Skill</h3>

        <div class="stat-number">
            <?php echo $averageSkill; ?>%
        </div>

    </div>


    <div class="stat-card">

        <h3>Job Applications</h3>

        <div class="stat-number">
            <?php echo $totalJobs; ?>
        </div>

    </div>


    <div class="stat-card">

        <h3>Selected</h3>

        <div class="stat-number">
            <?php echo $selectedJobs; ?>
        </div>

    </div>

</div>


<!-- SKILLS + ADD SKILL -->

<div class="grid">


<!-- ADD SKILL -->

<div class="card">

    <h2>🧠 Add Skill</h2>

    <form method="POST">


        <div class="form-group">

            <label>Skill Name</label>

            <input
                type="text"
                name="skill_name"
                placeholder="Example: Java"
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
                <option>Soft Skill</option>
                <option>Cloud</option>
                <option>Data Analytics</option>
                <option>AI / ML</option>
                <option>Other</option>

            </select>

        </div>


        <div class="form-group">

            <label>Skill Level (%)</label>

            <input
                type="number"
                name="progress"
                min="0"
                max="100"
                value="0"
                required
            >

        </div>


        <button
            type="submit"
            name="add_skill"
        >

            + Add Skill

        </button>

    </form>

</div>


<!-- MY SKILLS -->

<div class="card">

    <h2>🚀 My Skills</h2>


    <?php if ($skills->num_rows > 0): ?>


        <?php while ($skill = $skills->fetch_assoc()): ?>

            <div class="skill">


                <div class="skill-top">

                    <div>

                        <div class="skill-name">

                            <?php
                            echo htmlspecialchars(
                                $skill["skill_name"]
                            );
                            ?>

                        </div>

                        <div class="skill-category">

                            <?php
                            echo htmlspecialchars(
                                $skill["category"]
                            );
                            ?>

                        </div>

                    </div>


                    <strong>

                        <?php echo $skill["progress"]; ?>%

                    </strong>

                </div>


                <div class="progress-bg">

                    <div
                        class="progress"
                        style="width: <?php echo $skill["progress"]; ?>%;"
                    ></div>

                </div>


                <div class="skill-actions">

                    <form method="POST">

                        <input
                            type="hidden"
                            name="skill_id"
                            value="<?php echo $skill["id"]; ?>"
                        >

                        <input
                            type="number"
                            name="progress"
                            min="0"
                            max="100"
                            value="<?php echo $skill["progress"]; ?>"
                        >

                        <button
                            class="small-btn"
                            type="submit"
                            name="update_skill"
                        >

                            Update

                        </button>

                    </form>


                    <a
                        href="?delete_skill=<?php echo $skill["id"]; ?>"
                        class="delete"
                        onclick="return confirm('Delete this skill?');"
                    >

                        Delete

                    </a>

                </div>

            </div>


        <?php endwhile; ?>


    <?php else: ?>

        <div class="empty">

            No skills added yet.

        </div>

    <?php endif; ?>

</div>

</div>


<!-- JOB APPLICATIONS -->

<div class="card jobs-card">

    <h2>🏢 Job Applications</h2>


    <form method="POST">


        <div class="grid">


            <div>

                <div class="form-group">

                    <label>Company Name</label>

                    <input
                        type="text"
                        name="company_name"
                        placeholder="Example: TCS"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Job Role</label>

                    <input
                        type="text"
                        name="job_role"
                        placeholder="Example: Java Developer"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Application Date</label>

                    <input
                        type="date"
                        name="application_date"
                        value="<?php echo date('Y-m-d'); ?>"
                        required
                    >

                </div>

            </div>


            <div>

                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option>Applied</option>

                        <option>Interview</option>

                        <option>Selected</option>

                        <option>Rejected</option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Notes</label>

                    <textarea
                        name="notes"
                        rows="5"
                        placeholder="Example: Technical interview pending"
                    ></textarea>

                </div>

            </div>

        </div>


        <button
            type="submit"
            name="add_job"
        >

            + Add Job Application

        </button>

    </form>


    <br>


    <h2>📋 My Applications</h2>


    <?php if ($jobs->num_rows > 0): ?>


        <?php while ($job = $jobs->fetch_assoc()): ?>

            <div class="job">


                <div class="job-top">

                    <div>

                        <div class="company">

                            <?php
                            echo htmlspecialchars(
                                $job["company_name"]
                            );
                            ?>

                        </div>


                        <div class="role">

                            <?php
                            echo htmlspecialchars(
                                $job["job_role"]
                            );
                            ?>

                        </div>


                        <div class="job-info">

                            Applied on:

                            <?php
                            echo htmlspecialchars(
                                $job["application_date"]
                            );
                            ?>

                            <?php if (!empty($job["notes"])): ?>

                                •
                                <?php
                                echo htmlspecialchars(
                                    $job["notes"]
                                );
                                ?>

                            <?php endif; ?>

                        </div>

                    </div>


                    <div class="status">

                        <?php
                        echo htmlspecialchars(
                            $job["status"]
                        );
                        ?>

                    </div>

                </div>


                <div class="job-actions">


                    <form method="POST">

                        <input
                            type="hidden"
                            name="job_id"
                            value="<?php echo $job["id"]; ?>"
                        >


                        <select name="status">

                            <option
                                <?php
                                echo $job["status"] == "Applied"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Applied
                            </option>


                            <option
                                <?php
                                echo $job["status"] == "Interview"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Interview
                            </option>


                            <option
                                <?php
                                echo $job["status"] == "Selected"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Selected
                            </option>


                            <option
                                <?php
                                echo $job["status"] == "Rejected"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Rejected
                            </option>

                        </select>


                        <button
                            type="submit"
                            name="update_job"
                        >

                            Update

                        </button>

                    </form>


                    <a
                        href="?delete_job=<?php echo $job["id"]; ?>"
                        class="delete"
                        onclick="return confirm('Delete this application?');"
                    >

                        Delete

                    </a>

                </div>

            </div>


        <?php endwhile; ?>


    <?php else: ?>

        <div class="empty">

            No job applications yet.

        </div>

    <?php endif; ?>


</div>


</div>

</body>

</html>