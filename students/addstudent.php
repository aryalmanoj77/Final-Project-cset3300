<?php
session_start();
if (!isset($_SESSION['valid_user'])) {
    header("Location: ../login.php");
    exit;
}

// Database connection
$inifile = parse_ini_file("../myproperties.ini");
$conn = new mysqli($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"]);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $status = isset($_POST['status']) ? 1 : 0;

    if (!empty($name) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("INSERT INTO students (name, email, active) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $email, $status);
        $message = $stmt->execute() ? "Student added successfully." : "Error adding student.";
        $stmt->close();
    } else {
        $message = "Please provide a valid name and email.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Student</title>
    <style>
        label { width: 125px; float: left; font-weight: bold; }
        input, button { padding: 3px; margin: 5px 0; border: 1px solid #000; }
        button { margin-top: 10px; }
        p { clear: both; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Add Student</h1>
    <h3><a href="liststudents.php">Back to Students</a></h3>

    <!-- Success/Error Message -->
    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <!-- Add Student -->
    <form method="post">
        <p>
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" size="30" required />
        </p>
        <p>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" size="30" required />
        </p>
        <p>
            <label for="status">Active:</label>
            <input type="checkbox" name="status" id="status" value="1" checked />
        </p>
        <button type="submit">Add Student</button>
    </form>
</body>
</html>
