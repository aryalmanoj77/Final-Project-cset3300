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

$message = '';
$bookDetails = null;

// SANITIZE USER INPUT
function CleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
// DATE
function GetFormattedDate($string) {
    if (isset($string) && !empty($string)) {
        $date = strtotime($string);
        return $date !== false ? date('Y-M-d', $date) : htmlspecialchars($string);
    }
    return "No Date Found";
}

// DYNAMIC URLS FOR SORTING AND FILTERING
function RepopulateUrl($key, $value) {
    $tempGET = array_merge([], $_GET);
    $tempGET[$key] = $value;
    $url = "listcheckouts.php?" . http_build_query($tempGET);
    return $url;
}

// CHECKOUT STATUS
function KindCheckout() {
    if (isset($_GET['checkout'])) {
        switch ($_GET['checkout']) {
            case 'finished':
                return "finished";
            case 'active':
                return "active";
            default:
                return "both";
        }
    }
    return "both";
}

// HANDLES BOOK RETURN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_id'])) {
    $checkout_id = intval(CleanInput($_POST['checkout_id']));
    
    if ($checkout_id > 0) {
        $return_date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE checkouts SET return_date = ? WHERE id = ?");
        $stmt->bind_param("si", $return_date, $checkout_id);

        if ($stmt->execute()) {
            $message = "Book has been successfully returned.";

            // RETURNED BOOK DETAILS
            $bookStmt = $conn->prepare("
                SELECT b.title, b.author, c.return_date, c.book_id
                FROM books b
                JOIN checkouts c ON b.id = c.book_id 
                WHERE c.id = ?
            ");
            $bookStmt->bind_param("i", $checkout_id);
            $bookStmt->execute();
            $bookDetails = $bookStmt->get_result()->fetch_assoc();
            $bookStmt->close();
        } else {
            $message = "ERROR: Unable to mark the book as returned. Please try again.";
        }
        $stmt->close();
    } else {
        $message = "Invalid request. No valid checkout ID provided.";
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
        label { font-weight: bold; }
        button {
            padding: 5px 10px;
            margin-top: 10px;
            border: 1px solid #000;
            background-color: #f0f0f0;
            cursor: pointer;
        }
        button:hover {
            background-color: #d0d0d0;
        }
        p { margin-top: 10px; }
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
    </style>
</head>
<body>
    <h1>Book Returned</h1>

    <!-- SUCCESS/ERROR MESSAGE -->
    <?php if (!empty($message)): ?>
        <div class="message <?php echo isset($bookDetails) ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- BOOK DETAILS -->
    <?php if ($bookDetails): ?>
        <p><label>Title:</label> <?php echo htmlspecialchars($bookDetails['title']); ?></p>
        <p><label>Author:</label> <?php echo htmlspecialchars($bookDetails['author']); ?></p>
        <p><label>Return Date:</label> <?php echo GetFormattedDate($bookDetails['return_date']); ?></p>
        <p><a href="listcheckouts.php?filtercol0=bookid&filterstr0=<?php echo htmlspecialchars($bookDetails['book_id']); ?>">View Checkout History</a></p>
    <?php endif; ?>

    <!-- NAVIGATION FORM -->
    <form action="listcheckouts.php" method="get">
        <fieldset>
            <legend>Filter Options</legend>
            
            <!-- STATUS FILTER -->
            <label for="status">Checkout Status:</label>
            <input type="radio" name="checkout" id="both" value="both" <?php if (KindCheckout() == "both") echo "checked"; ?>>
            <label for="both">Both</label>
            <input type="radio" name="checkout" id="finished" value="finished" <?php if (KindCheckout() == "finished") echo "checked"; ?>>
            <label for="finished">Finished</label>
            <input type="radio" name="checkout" id="active" value="active" <?php if (KindCheckout() == "active") echo "checked"; ?>>
            <label for="active">Active</label>
            <br />

            <!-- SORT ORDER FILTER -->
            <label for="order">Sort By Title:</label>
            <select name="order" id="order">
                <option value="asc" <?php echo (isset($_GET['order']) && $_GET['order'] === 'asc') ? 'selected' : ''; ?>>A-Z</option>
                <option value="desc" <?php echo (isset($_GET['order']) && $_GET['order'] === 'desc') ? 'selected' : ''; ?>>Z-A</option>
            </select>
            <br />
        </fieldset>

        <!-- SUBMIT BUTTON -->
        <button type="submit">Back to Checkouts</button>
    </form>
</body>
</html>

