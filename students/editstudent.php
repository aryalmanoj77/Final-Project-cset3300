<?php
session_start(); // Start session

// Authentication
if (!isset($_SESSION['valid_user'])) {
    header("Location: login.php");
    exit;
}

// Parse the database configuration file for connection
$inifile = parse_ini_file("../myproperties.ini");
$conn = new mysqli($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"]);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle deactivation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate'])) {
    $student_id = $_POST['student_id'];

    // Prepare the query to deactivate the student
    $query = "UPDATE students SET active = 0 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);

    $message = $stmt->execute()
        ? "Student account has been deactivated successfully."
        : "Error: Unable to deactivate student account.";

    $stmt->close(); // Close statement
}

$conn->close(); // Close connection
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
    <style type="text/css">
        label {
            font-weight: bold;
        }
        input, button {
            border: 1px solid #000;
            padding: 5px;
            margin-top: 10px;
        }
        button {
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <h1>Edit Student</h1>

    <!-- Display success or error message -->
    <?php if (!empty($message)): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <!-- Form to deactivate student account -->
    <?php if (isset($_GET['id'])): ?>
        <form action="editstudent.php" method="post">
            <p>
                <label for="student_id">Student ID:</label>
                <input type="text" name="student_id" id="student_id" size="30" 
                    value="<?php echo htmlspecialchars($_GET['id']); ?>" readonly />
            </p>
            <p>
                <button type="submit" name="deactivate" onclick="return confirm('Are you sure you want to deactivate this student account?');">
                    Deactivate Student Account
                </button>
            </p>
        </form>
    <?php else: ?>
        <p>No student selected for editing.</p>
    <?php endif; ?>

    <!-- Link to go back -->
    <p><a href="liststudents.php">Back to Students</a></p>
</body>
</html>

