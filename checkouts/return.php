<?php
session_start(); 
if (!isset($_SESSION['valid_user'])) {
    header("Location: ../login.php");
    exit;
}

// DATABASE CONNECTION
require('../inc-stdmeta.php');
$inifile = parse_ini_file("../myproperties.ini");
$conn = new mysqli($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"]);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// CSRF PROTECTION
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// INITIALIZE VARIABLES
$message = '';
$bookDetails = null;

// SANITIZE INPUT
function CleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// FETCH BOOK DETAILS
function getBookDetails($conn, $checkout_id) {
    $stmt = $conn->prepare("
        SELECT b.title, c.rocketid, c.return_date
        FROM books b
        JOIN checkouts c ON b.id = c.book_id 
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $checkout_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

// HANDLES BOOK RETURN REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_id'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $checkout_id = intval(CleanInput($_POST['checkout_id']));
        if ($checkout_id > 0) {
            $return_date = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("UPDATE checkouts SET return_date = ? WHERE id = ?");
            $stmt->bind_param("si", $return_date, $checkout_id);

            if ($stmt->execute()) {
                $message = "Book has been successfully returned.";
                $bookDetails = getBookDetails($conn, $checkout_id);
            } else {
                $message = "ERROR: Unable to mark the book as returned. Please try again.";
            }
            $stmt->close();
        } else {
            $message = "Invalid request. No valid checkout ID provided.";
        }
    } else {
        $message = "Invalid CSRF token. Please try again.";
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = "Invalid request. No book specified for return.";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Returned</title>
    <style>
        h1, h2, h3, p, a {
            font-family: sans-serif;
            font-weight: lighter;
        }
        h4 {
            margin: 0em;
        }
        table {
            font-family: sans-serif;
            font-weight: lighter;
        }
        td {
            font-family: sans-serif;
            font-weight: lighter;
            padding: 0.75em 0.25em;
        }
        table, th, td {
            border: 2px solid black;
            border-collapse: collapse;
            width: fit-content;
            height: fit-content;
        }
        th {
            padding: 0.5em 0.25em;
        }
        body {
            background-color: beige;
            margin: 0.75em;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .button-link {
            border: 2px solid black;
            padding: 0.25em;
            text-align: center;
        }
    </style>
    <script>
        // POPUP FUNCTION
        function showPopup(title, rocketid, returnDate) {
            alert(`Book Title: ${title}\nRocket ID: ${rocketid}\nReturn Date: ${returnDate}`);
        }
    </script>
</head>
<body>
    <h1>Book Returned</h1>

    <!-- SUCCESS/ERROR MESSAGE -->
    <?php if (!empty($message)): ?>
        <div class="message <?php echo isset($bookDetails) ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- JS POPUP -->
    <?php if (!empty($bookDetails)): ?>
    <script>
        showPopup(
            <?php echo json_encode($bookDetails['title']); ?>,
            <?php echo json_encode($bookDetails['rocketid']); ?>,
            <?php echo json_encode($bookDetails['return_date']); ?>
        );
    </script>
    <?php endif; ?>

    <!-- BOOK DETAILS -->
    <?php if ($bookDetails): ?>
        <p><label>Title:</label> <?php echo htmlspecialchars($bookDetails['title']); ?></p>
        <p><label>Rocket ID:</label> <?php echo htmlspecialchars($bookDetails['rocketid']); ?></p>
        <p><label>Return Date:</label> <?php echo htmlspecialchars($bookDetails['return_date']); ?></p>
    <?php endif; ?>

    <!-- FILTER OPTIONS -->
    <form action="listcheckouts.php" method="get">
        <fieldset>
            <legend>Filter Options</legend>
            
            <!-- STATUS FILTER -->
            <label for="status">Checkout Status:</label>
            <input type="radio" name="checkout" id="both" value="both" checked>
            <label for="both">Both</label>
            <input type="radio" name="checkout" id="finished" value="finished">
            <label for="finished">Finished</label>
            <input type="radio" name="checkout" id="active" value="active">
            <label for="active">Active</label>
            <br />

            <!-- SORT ORDER FILTER -->
            <label for="order">Sort By Title:</label>
            <select name="order" id="order">
                <option value="asc">A-Z</option>
                <option value="desc">Z-A</option>
            </select>
            <br />
        </fieldset>

        <!-- SUBMIT BUTTON -->
        <button type="submit">Back to Checkouts</button>
    </form>
</body>
</html>

