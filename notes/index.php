<?php

session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION["user_id"];


/* =========================
   ADD NOTE
========================= */

if (isset($_POST["add_note"])) {

    $title = $_POST["title"];
    $category = $_POST["category"];
    $content = $_POST["content"];
    $important = isset($_POST["is_important"]) ? 1 : 0;

    $stmt = $conn->prepare(
        "INSERT INTO notes
        (user_id, title, category, content, is_important)
        VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "isssi",
        $userId,
        $title,
        $category,
        $content,
        $important
    );

    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   DELETE NOTE
========================= */

if (isset($_GET["delete"])) {

    $noteId = intval($_GET["delete"]);

    $stmt = $conn->prepare(
        "DELETE FROM notes
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param("ii", $noteId, $userId);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   UPDATE IMPORTANT
========================= */

if (isset($_GET["important"])) {

    $noteId = intval($_GET["important"]);

    $stmt = $conn->prepare(
        "UPDATE notes
         SET is_important =
             CASE
                WHEN is_important = 1 THEN 0
                ELSE 1
             END
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param("ii", $noteId, $userId);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   EDIT NOTE
========================= */

if (isset($_POST["update_note"])) {

    $noteId = intval($_POST["note_id"]);
    $title = $_POST["title"];
    $category = $_POST["category"];
    $content = $_POST["content"];
    $important = isset($_POST["is_important"]) ? 1 : 0;

    $stmt = $conn->prepare(
        "UPDATE notes
         SET title = ?,
             category = ?,
             content = ?,
             is_important = ?
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param(
        "sssiii",
        $title,
        $category,
        $content,
        $important,
        $noteId,
        $userId
    );

    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


/* =========================
   SEARCH
========================= */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if ($search != "") {

    $likeSearch = "%" . $search . "%";

    $stmt = $conn->prepare(
        "SELECT *
         FROM notes
         WHERE user_id = ?
         AND (title LIKE ?
              OR category LIKE ?
              OR content LIKE ?)
         ORDER BY is_important DESC, updated_at DESC"
    );

    $stmt->bind_param(
        "isss",
        $userId,
        $likeSearch,
        $likeSearch,
        $likeSearch
    );

} else {

    $stmt = $conn->prepare(
        "SELECT *
         FROM notes
         WHERE user_id = ?
         ORDER BY is_important DESC, updated_at DESC"
    );

    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$notes = $stmt->get_result();


/* =========================
   EDIT MODE
========================= */

$editNote = null;

if (isset($_GET["edit"])) {

    $editId = intval($_GET["edit"]);

    $stmt = $conn->prepare(
        "SELECT *
         FROM notes
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param("ii", $editId, $userId);
    $stmt->execute();

    $editNote = $stmt->get_result()->fetch_assoc();

    $stmt->close();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Notes | LifeOS</title>

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


/* TITLE */

h1 {
    font-size: 32px;
    margin-bottom: 8px;
}

.subtitle {
    color: #aaa4c0;
    margin-bottom: 25px;
}


/* SEARCH */

.search-box {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
}

.search-box input {
    flex: 1;
    padding: 13px;
    background: #17132d;
    color: white;
    border: 1px solid #39315e;
    border-radius: 10px;
}

.search-box button {
    width: auto;
    padding: 13px 22px;
}


/* MAIN GRID */

.grid {
    display: grid;
    grid-template-columns: 360px 1fr;
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
    min-height: 180px;
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

.checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 15px;
    color: #aaa4c0;
}

.checkbox input {
    width: auto;
}


/* NOTES */

.note {
    background: #111025;
    border: 1px solid #2c2550;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 15px;
}

.note:hover {
    border-color: #6545a8;
}

.note-top {
    display: flex;
    justify-content: space-between;
    gap: 15px;
}

.note-title {
    font-size: 19px;
    font-weight: bold;
}

.star {
    color: #ffd166;
}

.note-category {
    color: #b78cff;
    font-size: 13px;
    margin-top: 5px;
}

.note-content {
    color: #b5afc7;
    margin-top: 14px;
    line-height: 1.6;
    white-space: pre-wrap;
}

.note-date {
    color: #77718d;
    font-size: 12px;
    margin-top: 15px;
}

.actions {
    display: flex;
    gap: 8px;
    margin-top: 15px;
}

.actions a {
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
}

.edit {
    background: #30275a;
    color: #d7caff;
}

.important {
    background: #3b321e;
    color: #ffd166;
}

.delete {
    background: #391c31;
    color: #ff6b8a;
}

.empty {
    text-align: center;
    color: #aaa4c0;
    padding: 40px 20px;
}


/* RESPONSIVE */

@media(max-width:850px) {

    .grid {
        grid-template-columns: 1fr;
    }

}

@media(max-width:500px) {

    .search-box {
        flex-direction: column;
    }

    .search-box button {
        width: 100%;
    }

    .note-top {
        flex-direction: column;
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


<h1>Notes 📝</h1>

<p class="subtitle">
    Save your study notes, ideas, plans and important information.
</p>


<!-- SEARCH -->

<form
    method="GET"
    class="search-box"
>

    <input
        type="text"
        name="search"
        placeholder="Search your notes..."
        value="<?php echo htmlspecialchars($search); ?>"
    >

    <button type="submit">
        🔍 Search
    </button>

</form>


<div class="grid">


<!-- ADD / EDIT NOTE -->

<div class="card">

    <?php if ($editNote): ?>

        <h2>✏️ Edit Note</h2>

        <form method="POST">

            <input
                type="hidden"
                name="note_id"
                value="<?php echo $editNote["id"]; ?>"
            >

            <div class="form-group">

                <label>Title</label>

                <input
                    type="text"
                    name="title"
                    value="<?php echo htmlspecialchars($editNote["title"]); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Category</label>

                <select name="category">

                    <option
                        <?php echo $editNote["category"] == "Study" ? "selected" : ""; ?>
                    >
                        Study
                    </option>

                    <option
                        <?php echo $editNote["category"] == "Career" ? "selected" : ""; ?>
                    >
                        Career
                    </option>

                    <option
                        <?php echo $editNote["category"] == "Personal" ? "selected" : ""; ?>
                    >
                        Personal
                    </option>

                    <option
                        <?php echo $editNote["category"] == "Ideas" ? "selected" : ""; ?>
                    >
                        Ideas
                    </option>

                    <option
                        <?php echo $editNote["category"] == "Other" ? "selected" : ""; ?>
                    >
                        Other
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>Content</label>

                <textarea
                    name="content"
                    required
                ><?php echo htmlspecialchars($editNote["content"]); ?></textarea>

            </div>


            <div class="checkbox">

                <input
                    type="checkbox"
                    name="is_important"
                    <?php echo $editNote["is_important"] ? "checked" : ""; ?>
                >

                Mark as important ⭐

            </div>


            <button
                type="submit"
                name="update_note"
            >

                Update Note

            </button>

        </form>


        <br>

        <a
            href="index.php"
            style="color:#aaa4c0;"
        >
            Cancel Edit
        </a>


    <?php else: ?>

        <h2>➕ Add Note</h2>

        <form method="POST">


            <div class="form-group">

                <label>Title</label>

                <input
                    type="text"
                    name="title"
                    placeholder="Example: Java Interview Questions"
                    required
                >

            </div>


            <div class="form-group">

                <label>Category</label>

                <select name="category">

                    <option>Study</option>
                    <option>Career</option>
                    <option>Personal</option>
                    <option>Ideas</option>
                    <option>Other</option>

                </select>

            </div>


            <div class="form-group">

                <label>Content</label>

                <textarea
                    name="content"
                    placeholder="Write your note here..."
                    required
                ></textarea>

            </div>


            <div class="checkbox">

                <input
                    type="checkbox"
                    name="is_important"
                >

                Mark as important ⭐

            </div>


            <button
                type="submit"
                name="add_note"
            >

                + Save Note

            </button>

        </form>

    <?php endif; ?>

</div>


<!-- NOTES LIST -->

<div class="card">

    <h2>📚 My Notes</h2>


    <?php if ($notes->num_rows > 0): ?>


        <?php while ($note = $notes->fetch_assoc()): ?>

            <div class="note">


                <div class="note-top">

                    <div>

                        <div class="note-title">

                            <?php
                            echo htmlspecialchars(
                                $note["title"]
                            );
                            ?>

                            <?php if ($note["is_important"]): ?>

                                <span class="star">★</span>

                            <?php endif; ?>

                        </div>


                        <div class="note-category">

                            <?php
                            echo htmlspecialchars(
                                $note["category"]
                            );
                            ?>

                        </div>

                    </div>

                </div>


                <div class="note-content">

                    <?php
                    echo htmlspecialchars(
                        $note["content"]
                    );
                    ?>

                </div>


                <div class="note-date">

                    Last updated:
                    <?php
                    echo htmlspecialchars(
                        $note["updated_at"]
                    );
                    ?>

                </div>


                <div class="actions">

                    <a
                        href="?edit=<?php echo $note["id"]; ?>"
                        class="edit"
                    >
                        ✏️ Edit
                    </a>


                    <a
                        href="?important=<?php echo $note["id"]; ?>"
                        class="important"
                    >

                        <?php
                        echo $note["is_important"]
                            ? "☆ Unstar"
                            : "⭐ Important";
                        ?>

                    </a>


                    <a
                        href="?delete=<?php echo $note["id"]; ?>"
                        class="delete"
                        onclick="return confirm('Delete this note?');"
                    >
                        🗑 Delete
                    </a>

                </div>


            </div>


        <?php endwhile; ?>


    <?php else: ?>

        <div class="empty">

            <?php if ($search != ""): ?>

                🔍 No notes found for
                "<strong><?php echo htmlspecialchars($search); ?></strong>"

            <?php else: ?>

                📝 No notes yet.

                <br><br>

                Create your first note from the left.

            <?php endif; ?>

        </div>

    <?php endif; ?>


</div>

</div>

</div>

</body>

</html>