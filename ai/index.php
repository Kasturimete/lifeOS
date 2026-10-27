<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$userName = $_SESSION["user_name"] ?? "User";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>LifeOS AI Copilot</title>


<style>

/* =========================================
   RESET
========================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}


/* =========================================
   BODY
========================================= */

body {

    background:
        radial-gradient(
            circle at top left,
            rgba(124, 58, 237, 0.20),
            transparent 35%
        ),
        #090613;

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

    position: fixed;

    top: 0;
    bottom: 0;
    left: 0;

    padding: 25px 18px;

    background:
        rgba(10, 7, 20, 0.97);

    border-right:
        1px solid
        rgba(255,255,255,0.08);

}


.logo {

    font-size: 27px;

    font-weight: bold;

    margin-bottom: 35px;

}


.logo span {

    color: #a855f7;

}


.menu-title {

    font-size: 10px;

    letter-spacing: 2px;

    color: #666;

    margin:
        20px 10px 10px;

}


.sidebar a {

    display: block;

    text-decoration: none;

    color: #999;

    padding: 13px 14px;

    margin: 5px 0;

    border-radius: 12px;

    transition: 0.25s;

}


.sidebar a:hover {

    background:
        rgba(139,92,246,0.12);

    color: white;

    transform:
        translateX(3px);

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
        rgba(124,58,237,0.25);

}


/* =========================================
   MAIN
========================================= */

.main {

    margin-left: 250px;

    width: calc(100% - 250px);

    min-height: 100vh;

}


/* =========================================
   TOPBAR
========================================= */

.topbar {

    height: 80px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 35px;

    border-bottom:
        1px solid
        rgba(255,255,255,0.06);

}


.search {

    width: 390px;

    padding: 13px 18px;

    border-radius: 12px;

    border:
        1px solid
        rgba(255,255,255,0.08);

    background:
        rgba(255,255,255,0.03);

    color: white;

    outline: none;

}


.profile {

    display: flex;

    align-items: center;

    gap: 12px;

}


.avatar {

    width: 42px;

    height: 42px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #9333ea,
            #6366f1
        );

    font-weight: bold;

}


/* =========================================
   PAGE
========================================= */

.page {

    max-width: 1100px;

    margin: auto;

    padding: 45px 35px 70px;

}


/* =========================================
   HEADER
========================================= */

.page-header {

    text-align: center;

    margin-bottom: 35px;

}


.ai-icon {

    width: 70px;

    height: 70px;

    margin: auto;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 22px;

    font-size: 32px;

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #a855f7
        );

    box-shadow:
        0 15px 45px
        rgba(124,58,237,0.35);

}


.page-header h1 {

    font-size: 38px;

    margin-top: 20px;

}


.page-header h1 span {

    color: #a855f7;

}


.page-header p {

    color: #888;

    margin-top: 10px;

    font-size: 15px;

}


.status {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    margin-top: 18px;

    padding: 8px 15px;

    border-radius: 30px;

    border:
        1px solid
        rgba(168,85,247,0.4);

    background:
        rgba(168,85,247,0.08);

    color: #c084fc;

    font-size: 13px;

}


.status-dot {

    width: 8px;

    height: 8px;

    border-radius: 50%;

    background: #22c55e;

}


/* =========================================
   CHAT BOX
========================================= */

.chat-container {

    background:
        rgba(18,14,34,0.92);

    border:
        1px solid
        rgba(168,85,247,0.25);

    border-radius: 22px;

    overflow: hidden;

    box-shadow:
        0 25px 80px
        rgba(0,0,0,0.35);

}


/* =========================================
   CHAT HEADER
========================================= */

.chat-header {

    padding: 20px 25px;

    border-bottom:
        1px solid
        rgba(255,255,255,0.07);

    display: flex;

    align-items: center;

    gap: 12px;

}


.bot-avatar {

    width: 45px;

    height: 45px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #9333ea
        );

    font-size: 22px;

}


.chat-title strong {

    display: block;

    font-size: 16px;

}


.chat-title small {

    color: #777;

}


/* =========================================
   MESSAGES
========================================= */

.messages {

    height: 420px;

    overflow-y: auto;

    padding: 25px;

}


.messages::-webkit-scrollbar {

    width: 6px;

}


.messages::-webkit-scrollbar-thumb {

    background: #3b285f;

    border-radius: 10px;

}


.message {

    max-width: 80%;

    padding: 17px 20px;

    margin-bottom: 18px;

    border-radius: 17px;

    line-height: 1.6;

    font-size: 15px;

}


.message small {

    display: block;

    font-size: 11px;

    margin-bottom: 6px;

    color: #999;

}


/* USER */

.user-message {

    margin-left: auto;

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #5b21b6
        );

    border-bottom-right-radius: 5px;

}


/* AI */

.ai-message {

    margin-right: auto;

    background:
        #21183b;

    border:
        1px solid
        rgba(168,85,247,0.18);

    border-bottom-left-radius: 5px;

}


/* =========================================
   SUGGESTIONS
========================================= */

.suggestions {

    padding:
        0 25px 20px;

}


.suggestions-title {

    color: #777;

    font-size: 12px;

    margin-bottom: 12px;

}


.suggestion-list {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

}


.suggestion {

    border:
        1px solid
        rgba(168,85,247,0.25);

    background:
        rgba(168,85,247,0.07);

    color: #c4b5fd;

    padding: 10px 14px;

    border-radius: 20px;

    cursor: pointer;

    font-size: 13px;

    transition: 0.2s;

}


.suggestion:hover {

    background:
        rgba(168,85,247,0.18);

    border-color:
        #a855f7;

    transform:
        translateY(-2px);

}


/* =========================================
   INPUT AREA
========================================= */

.input-area {

    padding: 20px 25px 25px;

    border-top:
        1px solid
        rgba(255,255,255,0.07);

}


.input-box {

    display: flex;

    align-items: center;

    gap: 10px;

}


#userInput {

    flex: 1;

    padding: 16px 18px;

    border-radius: 14px;

    border:
        1px solid
        rgba(255,255,255,0.1);

    background:
        #0e0a1b;

    color: white;

    outline: none;

    font-size: 15px;

}


#userInput:focus {

    border-color:
        #8b5cf6;

    box-shadow:
        0 0 0 3px
        rgba(139,92,246,0.1);

}


#sendButton {

    width: 52px;

    height: 52px;

    border: none;

    border-radius: 14px;

    cursor: pointer;

    color: white;

    font-size: 20px;

    background:
        linear-gradient(
            135deg,
            #7c3aed,
            #9333ea
        );

    transition: 0.2s;

}


#sendButton:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 10px 25px
        rgba(124,58,237,0.35);

}


#sendButton:disabled {

    opacity: 0.5;

    cursor: not-allowed;

    transform: none;

}


.input-note {

    color: #666;

    font-size: 11px;

    margin-top: 10px;

    text-align: center;

}


/* =========================================
   INFO CARDS
========================================= */

.info-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-top: 25px;

}


.info-card {

    padding: 22px;

    background:
        rgba(18,14,34,0.75);

    border:
        1px solid
        rgba(255,255,255,0.07);

    border-radius: 17px;

}


.info-card .icon {

    font-size: 25px;

    margin-bottom: 12px;

}


.info-card h3 {

    font-size: 15px;

    margin-bottom: 8px;

}


.info-card p {

    color: #777;

    font-size: 13px;

    line-height: 1.6;

}


/* =========================================
   MOBILE
========================================= */

@media(max-width: 900px) {

    .sidebar {

        display: none;

    }

    .main {

        margin-left: 0;

        width: 100%;

    }

    .page {

        padding: 30px 18px;

    }

    .topbar {

        padding: 0 18px;

    }

    .search {

        width: 200px;

    }

    .info-grid {

        grid-template-columns: 1fr;

    }

}


@media(max-width: 600px) {

    .search {

        display: none;

    }

    .page-header h1 {

        font-size: 29px;

    }

    .messages {

        height: 400px;

        padding: 18px;

    }

    .message {

        max-width: 90%;

    }

}


/* =========================================
   ANIMATION
========================================= */

@keyframes pulse {

    0% {
        box-shadow:
            0 0 0 0
            rgba(168,85,247,0.4);
    }

    70% {
        box-shadow:
            0 0 0 12px
            rgba(168,85,247,0);
    }

    100% {
        box-shadow:
            0 0 0 0
            rgba(168,85,247,0);
    }

}


.ai-icon {

    animation: pulse 2.5s infinite;

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


    <a href="../dashboard.php">
        🏠 Dashboard
    </a>


    <a href="../goals/">
        🎯 Goals
    </a>


    <a href="../planner/">
        📋 Planner
    </a>


    <a href="../habits/">
        🔥 Habits
    </a>


    <a href="../finance/">
        💰 Finance
    </a>


    <a href="../learning/">
        📚 Learning
    </a>


    <a href="../career/">
        💼 Career
    </a>


    <a href="../notes/">
        📝 Notes
    </a>


    <div class="menu-title">
        INTELLIGENCE
    </div>


    <a href="../analytics/">
        📊 Analytics
    </a>


    <a href="index.php" class="active">
        🤖 AI Copilot
    </a>


    <div class="menu-title">
        ACCOUNT
    </div>


    <a href="../logout.php">
        🚪 Logout
    </a>


</aside>



<!-- =====================================
     MAIN
===================================== -->

<main class="main">


<!-- TOPBAR -->

<header class="topbar">


    <input
        class="search"
        type="text"
        placeholder="🔍 Search your life..."
    >


    <div class="profile">


        <span style="font-size:20px;">
            🔔
        </span>


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

                echo htmlspecialchars(
                    $userName
                );

                ?>

            </strong>


            <br>


            <small style="color:#777;">
                Personal
            </small>

        </div>


    </div>


</header>



<!-- =====================================
     PAGE
===================================== -->

<section class="page">


<!-- PAGE HEADER -->

<div class="page-header">


    <div class="ai-icon">
        ✨
    </div>


    <h1>

        Meet your
        <span>AI Life Copilot</span>

    </h1>


    <p>

        Your personal assistant for goals,
        productivity, habits, learning and finance.

    </p>


    <div class="status">

        <span class="status-dot"></span>

        LifeOS Intelligence Active

    </div>


</div>



<!-- =====================================
     CHAT
===================================== -->

<div class="chat-container">


    <!-- CHAT HEADER -->

    <div class="chat-header">


        <div class="bot-avatar">
            🤖
        </div>


        <div class="chat-title">

            <strong>
                AI Life Copilot
            </strong>

            <small>
                Your personal LifeOS assistant
            </small>

        </div>


    </div>



    <!-- MESSAGES -->

    <div
        class="messages"
        id="messages"
    >


        <!-- WELCOME MESSAGE -->

        <div class="message ai-message">

            <small>
                🤖 AI Life Copilot
            </small>


            Hi
            <strong>
                <?php
                echo htmlspecialchars($userName);
                ?>
            </strong>
            👋


            <br><br>


            I'm your LifeOS Copilot.


            <br><br>


            I can help you understand your
            <strong>goals, tasks, habits, learning
            and finances</strong>.


            <br><br>


            What would you like to focus on today?

        </div>


    </div>



    <!-- =================================
         SUGGESTIONS
    ================================== -->

    <div class="suggestions">


        <div class="suggestions-title">

            Try asking:

        </div>


        <div class="suggestion-list">


            <button
                type="button"
                class="suggestion"
                onclick="askQuestion('What should I focus on today?')"
            >

                🎯 What should I focus on today?

            </button>


            <button
                type="button"
                class="suggestion"
                onclick="askQuestion('How are my goals?')"
            >

                🎯 How are my goals?

            </button>


            <button
                type="button"
                class="suggestion"
                onclick="askQuestion('Show my habits')"
            >

                🔥 Show my habits

            </button>


            <button
                type="button"
                class="suggestion"
                onclick="askQuestion('How is my finance?')"
            >

                💰 How is my finance?

            </button>


            <button
                type="button"
                class="suggestion"
                onclick="askQuestion('Help me with my study')"
            >

                📚 Help me with my study

            </button>


            <button
                type="button"
                class="suggestion"
                onclick="askQuestion('Give me my life summary')"
            >

                📊 My life summary

            </button>


        </div>

    </div>



    <!-- =================================
         INPUT
    ================================== -->

    <div class="input-area">


        <div class="input-box">


            <input
                type="text"
                id="userInput"
                placeholder="Ask your LifeOS Copilot..."
                autocomplete="off"
            >


            <button
                type="button"
                id="sendButton"
                onclick="sendMessage()"
            >

                ➤

            </button>


        </div>


        <div class="input-note">

            LifeOS Copilot uses your personal
            LifeOS data to provide smart suggestions.

        </div>


    </div>


</div>



<!-- =====================================
     INFO CARDS
===================================== -->

<div class="info-grid">


    <div class="info-card">


        <div class="icon">
            🎯
        </div>


        <h3>
            Goal Guidance
        </h3>


        <p>

            Get suggestions based on your
            current goals and progress.

        </p>


    </div>



    <div class="info-card">


        <div class="icon">
            🔥
        </div>


        <h3>
            Habit Coach
        </h3>


        <p>

            Track your habits and maintain
            your productivity streaks.

        </p>


    </div>



    <div class="info-card">


        <div class="icon">
            💰
        </div>


        <h3>
            Finance Insights
        </h3>


        <p>

            Understand your income,
            expenses and balance.

        </p>


    </div>


</div>


</section>


</main>


</div>



<script>

/* =========================================
   SEND MESSAGE
========================================= */

function sendMessage() {


    const input =
        document.getElementById(
            "userInput"
        );


    const messages =
        document.getElementById(
            "messages"
        );


    const sendButton =
        document.getElementById(
            "sendButton"
        );


    const message =
        input.value.trim();


    if (message === "") {

        return;

    }


    /* =====================================
       USER MESSAGE
    ===================================== */

    const userMessage =
        document.createElement("div");


    userMessage.className =
        "message user-message";


    userMessage.innerHTML =

        "<small>You</small>" +

        escapeHtml(message);


    messages.appendChild(
        userMessage
    );


    /* Clear input */

    input.value = "";


    /* Disable button */

    sendButton.disabled = true;


    /* Scroll */

    messages.scrollTop =
        messages.scrollHeight;



    /* =====================================
       LOADING MESSAGE
    ===================================== */

    const loading =
        document.createElement("div");


    loading.className =
        "message ai-message";


    loading.innerHTML =

        "<small>🤖 AI Life Copilot</small>" +

        "Thinking... 🤔";


    messages.appendChild(
        loading
    );


    messages.scrollTop =
        messages.scrollHeight;



    /* =====================================
       FORM DATA
    ===================================== */

    const formData =
        new FormData();


    formData.append(
        "question",
        message
    );



    /* =====================================
       PHP API REQUEST
    ===================================== */

    fetch("ai_api.php", {

        method: "POST",

        body: formData

    })


    .then(async function(response) {


        const text =
            await response.text();


        console.log(
            "AI API RAW RESPONSE:",
            text
        );


        /*
           Convert PHP response
           into JSON
        */

        try {

            return JSON.parse(text);

        }

        catch (error) {

            throw new Error(
                "PHP returned invalid response: " +
                text
            );

        }

    })


    .then(function(data) {


        /* Remove loading */

        loading.remove();


        /* Create AI message */

        const aiMessage =
            document.createElement("div");


        aiMessage.className =
            "message ai-message";


        aiMessage.innerHTML =

            "<small>🤖 AI Life Copilot</small>" +


            (
                data.success

                ? data.message

                : "❌ " +
                  escapeHtml(
                      data.message ||
                      "Something went wrong."
                  )
            );


        messages.appendChild(
            aiMessage
        );


        messages.scrollTop =
            messages.scrollHeight;


    })


    .catch(function(error) {


        /* Remove loading */

        loading.remove();


        /* Error message */

        const errorMessage =
            document.createElement("div");


        errorMessage.className =
            "message ai-message";


        errorMessage.innerHTML =

            "<small>🤖 AI Life Copilot</small>" +

            "❌ Error: " +

            escapeHtml(
                error.message
            );


        messages.appendChild(
            errorMessage
        );


        messages.scrollTop =
            messages.scrollHeight;


        console.error(
            "LifeOS AI Error:",
            error
        );


    })


    .finally(function() {


        sendButton.disabled =
            false;


        input.focus();


    });

}



/* =========================================
   SUGGESTION QUESTIONS
========================================= */

function askQuestion(question) {


    const input =
        document.getElementById(
            "userInput"
        );


    input.value =
        question;


    sendMessage();

}



/* =========================================
   ENTER KEY
========================================= */

document
    .getElementById("userInput")
    .addEventListener(
        "keydown",
        function(event) {


            if (
                event.key === "Enter"
            ) {

                event.preventDefault();

                sendMessage();

            }

        }
    );



/* =========================================
   HTML SECURITY
========================================= */

function escapeHtml(text) {


    const div =
        document.createElement(
            "div"
        );


    div.textContent =
        text;


    return div.innerHTML;

}

</script>


</body>

</html>