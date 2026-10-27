<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION["user_id"];

// Add Transaction
if (isset($_POST["add_transaction"])) {

    $type = $_POST["type"];
    $amount = $_POST["amount"];
    $category = $_POST["category"];
    $payment_method = $_POST["payment_method"];
    $description = $_POST["description"];
    $transaction_date = $_POST["transaction_date"];

    $stmt = $conn->prepare(
        "INSERT INTO transactions
        (user_id, type, amount, category, payment_method, description, transaction_date)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "isdssss",
        $userId,
        $type,
        $amount,
        $category,
        $payment_method,
        $description,
        $transaction_date
    );

    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


// Delete Transaction
if (isset($_GET["delete"])) {

    $id = intval($_GET["delete"]);

    $stmt = $conn->prepare(
        "DELETE FROM transactions WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param("ii", $id, $userId);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}


// Total Income
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount),0) AS total
     FROM transactions
     WHERE user_id = ? AND type = 'Income'"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$totalIncome = $result->fetch_assoc()["total"];

$stmt->close();


// Total Expense
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount),0) AS total
     FROM transactions
     WHERE user_id = ? AND type = 'Expense'"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$totalExpense = $result->fetch_assoc()["total"];

$stmt->close();


// Balance
$balance = $totalIncome - $totalExpense;


// Recent Transactions
$stmt = $conn->prepare(
    "SELECT *
     FROM transactions
     WHERE user_id = ?
     ORDER BY transaction_date DESC, id DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$transactions = $stmt->get_result();


// Category wise expenses
$stmt = $conn->prepare(
    "SELECT category, SUM(amount) AS total
     FROM transactions
     WHERE user_id = ? AND type = 'Expense'
     GROUP BY category"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$categoryResult = $stmt->get_result();

$categories = [];
$categoryAmounts = [];

while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row["category"];
    $categoryAmounts[] = $row["total"];
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Finance | LifeOS</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #0d0b1f;
            color: white;
            min-height: 100vh;
        }

        .container {
            width: 92%;
            max-width: 1200px;
            margin: auto;
            padding: 30px 0;
        }

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

        h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #aaa4c0;
            margin-bottom: 25px;
        }

        /* Cards */

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: #17132d;
            border: 1px solid #2c2550;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(0,0,0,.2);
        }

        .card h3 {
            color: #aaa4c0;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .amount {
            font-size: 28px;
            font-weight: bold;
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

        /* Main */

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-card,
        .chart-card,
        .transactions-card {
            background: #17132d;
            border: 1px solid #2c2550;
            border-radius: 18px;
            padding: 25px;
        }

        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
        }

        form {
            display: grid;
            gap: 14px;
        }

        label {
            color: #aaa4c0;
            font-size: 14px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #39315e;
            background: #0e0b20;
            color: white;
            outline: none;
        }

        textarea {
            resize: none;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #9b6cff;
        }

        button {
            border: none;
            padding: 13px;
            border-radius: 10px;
            background: linear-gradient(135deg, #8f5cff, #c06cff);
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            opacity: .9;
        }

        /* Transactions */

        .transactions-card {
            margin-top: 25px;
        }

        .transaction {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #2c2550;
        }

        .transaction:last-child {
            border-bottom: none;
        }

        .transaction-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .transaction-info {
            color: #9993ad;
            font-size: 13px;
        }

        .transaction-right {
            text-align: right;
        }

        .delete {
            display: inline-block;
            margin-top: 5px;
            color: #ff6b8a;
            text-decoration: none;
            font-size: 12px;
        }

        .empty {
            color: #9993ad;
            text-align: center;
            padding: 25px;
        }

        @media(max-width: 800px) {

            .stats {
                grid-template-columns: 1fr;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .transaction {
                gap: 15px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="top">

        <div class="logo">
            ✦ LifeOS
        </div>

        <a href="../dashboard.php" class="back">
            ← Dashboard
        </a>

    </div>


    <h1>Finance 💰</h1>

    <p class="subtitle">
        Track your income, expenses and financial progress.
    </p>


    <!-- Statistics -->

    <div class="stats">

        <div class="card">

            <h3>Total Income</h3>

            <div class="amount income">
                ₹<?php echo number_format($totalIncome, 2); ?>
            </div>

        </div>


        <div class="card">

            <h3>Total Expenses</h3>

            <div class="amount expense">
                ₹<?php echo number_format($totalExpense, 2); ?>
            </div>

        </div>


        <div class="card">

            <h3>Current Balance</h3>

            <div class="amount balance">
                ₹<?php echo number_format($balance, 2); ?>
            </div>

        </div>

    </div>


    <!-- Form + Chart -->

    <div class="grid">

        <!-- Add Transaction -->

        <div class="form-card">

            <h2 class="section-title">
                Add Transaction
            </h2>

            <form method="POST">

                <div>

                    <label>Type</label>

                    <select name="type" required>

                        <option value="Income">
                            Income
                        </option>

                        <option value="Expense">
                            Expense
                        </option>

                    </select>

                </div>


                <div>

                    <label>Amount</label>

                    <input
                        type="number"
                        name="amount"
                        step="0.01"
                        min="1"
                        placeholder="Enter amount"
                        required
                    >

                </div>


                <div>

                    <label>Category</label>

                    <select name="category" required>

                        <option value="">Select Category</option>

                        <option>Food</option>
                        <option>Travel</option>
                        <option>Shopping</option>
                        <option>Education</option>
                        <option>Bills</option>
                        <option>Health</option>
                        <option>Entertainment</option>
                        <option>Salary</option>
                        <option>Freelance</option>
                        <option>Other</option>

                    </select>

                </div>


                <div>

                    <label>Payment Method</label>

                    <select name="payment_method">

                        <option>UPI</option>
                        <option>Cash</option>
                        <option>Debit Card</option>
                        <option>Credit Card</option>
                        <option>Bank Transfer</option>

                    </select>

                </div>


                <div>

                    <label>Description</label>

                    <textarea
                        name="description"
                        rows="3"
                        placeholder="Example: College fees"
                    ></textarea>

                </div>


                <div>

                    <label>Date</label>

                    <input
                        type="date"
                        name="transaction_date"
                        value="<?php echo date('Y-m-d'); ?>"
                        required
                    >

                </div>


                <button type="submit" name="add_transaction">

                    + Add Transaction

                </button>

            </form>

        </div>


        <!-- Chart -->

        <div class="chart-card">

            <h2 class="section-title">
                Spending by Category 📊
            </h2>

            <canvas id="expenseChart"></canvas>

        </div>

    </div>


    <!-- Transactions -->

    <div class="transactions-card">

        <h2 class="section-title">
            Recent Transactions
        </h2>


        <?php if ($transactions->num_rows > 0): ?>

            <?php while ($row = $transactions->fetch_assoc()): ?>

                <div class="transaction">

                    <div>

                        <div class="transaction-name">

                            <?php echo htmlspecialchars($row["category"]); ?>

                        </div>

                        <div class="transaction-info">

                            <?php echo htmlspecialchars($row["description"]); ?>

                            •

                            <?php echo htmlspecialchars($row["payment_method"]); ?>

                            •

                            <?php echo htmlspecialchars($row["transaction_date"]); ?>

                        </div>

                    </div>


                    <div class="transaction-right">

                        <div class="<?php echo $row["type"] == "Income" ? "income" : "expense"; ?>">

                            <?php echo $row["type"] == "Income" ? "+" : "-"; ?>

                            ₹<?php echo number_format($row["amount"], 2); ?>

                        </div>


                        <a
                            href="?delete=<?php echo $row["id"]; ?>"
                            class="delete"
                            onclick="return confirm('Delete this transaction?');"
                        >
                            Delete
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                No transactions yet. Add your first transaction above.
            </div>

        <?php endif; ?>

    </div>

</div>


<script>

const categories = <?php echo json_encode($categories); ?>;

const amounts = <?php echo json_encode($categoryAmounts); ?>;


new Chart(document.getElementById("expenseChart"), {

    type: "doughnut",

    data: {

        labels: categories,

        datasets: [{

            data: amounts,

            borderWidth: 0

        }]

    },

    options: {

        responsive: true,

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