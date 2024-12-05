<?php
require('../isauthenticated.php');

$rocketid = $name = $phone = $address = "";
$rocketidErr = $nameErr = $phoneErr = $addressErr = "";
$success = false;
$error = "";

// Get most recent student
$inifile = parse_ini_file("../myproperties.ini");
$conn = mysqli_connect($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"])
    or die("Connection failed: " . mysqli_connect_error());

$recentStudentQuery = "SELECT rocketid, name, phone, address, create_dt FROM student ORDER BY create_dt DESC LIMIT 1";
$result = mysqli_query($conn, $recentStudentQuery);
$recentStudent = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate Rocket ID
    if (empty($_POST["rocketid"])) {
        $rocketidErr = "Rocket ID is required";
    } else {
        $rocketid = CleanInput($_POST["rocketid"]);
        if (strlen($rocketid) > 10) {
            $rocketidErr = "Rocket ID must be 10 characters or less";
        } else {
            // Check if Rocket ID already exists
            $stmt = $conn->prepare("SELECT rocketid FROM student WHERE rocketid = ?");
            $stmt->bind_param("s", $rocketid);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $rocketidErr = "Rocket ID already exists";
            }
            $stmt->close();
        }
    }
    
    // Validate name
    if (empty($_POST["name"])) {
        $nameErr = "Name is required";
    } else {
        $name = CleanInput($_POST["name"]);
        if (strlen($name) > 50) {
            $nameErr = "Name must be 50 characters or less";
        }
    }
    
    // Validate phone
    if (empty($_POST["phone"])) {
        $phoneErr = "Phone is required";
    } else {
        $phone = CleanInput($_POST["phone"]);
        if (strlen($phone) > 15) {
            $phoneErr = "Phone must be 15 characters or less";
        }
    }
    
    // Validate address
    if (empty($_POST["address"])) {
        $addressErr = "Address is required";
    } else {
        $address = CleanInput($_POST["address"]);
        if (strlen($address) > 50) {
            $addressErr = "Address must be 50 characters or less";
        }
    }
    
    // If no errors, insert into database
    if (empty($rocketidErr) && empty($nameErr) && empty($phoneErr) && empty($addressErr)) {
        $sql = "INSERT INTO student (rocketid, name, phone, address) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $rocketid, $name, $phone, $address);
        
        if ($stmt->execute()) {
            $success = true;
            $rocketid = $name = $phone = $address = "";
            
            // Refresh recent student
            $result = mysqli_query($conn, $recentStudentQuery);
            $recentStudent = mysqli_fetch_assoc($result);
        } else {
            $error = "Error adding student: " . $conn->error;
        }
        
        $stmt->close();
    }
}

<<<<<<< HEAD
function CleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function GetFormattedDate($string) {
    if(isset($string) && !empty($string)) {
        $date = strtotime($string);
        if($date !== false) return date('Y-M-d', $date);
        return htmlspecialchars($string);
=======
// DATABASE CONNECTION
require('../inc-stdmeta.php');
$inifile = parse_ini_file("../myproperties.ini");
$conn = new mysqli($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"]);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// FORM SUBMISSION
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
>>>>>>> f0c7a39111c8d69bcfd07a62a69f26345449075b
    }
    return "No Date Found";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require('../inc-stdmeta.php'); ?>
    <title>Add Student | CSET Library</title>
</head>
<body>
    <h1>Add Student</h1>
<<<<<<< HEAD
    <h3>CSET Department Student Library</h3>
    
    <?php if ($success): ?>
        <div class="success-message">Student successfully added.</div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error-message"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="add-form">
        <table>
            <tr>
                <td><label for="rocketid">Rocket ID:</label></td>
                <td>
                    <input type="text" id="rocketid" name="rocketid" 
                           value="<?= htmlspecialchars($rocketid); ?>" maxlength="10">
                    <span class="error"><?= $rocketidErr; ?></span>
                </td>
            </tr>
            <tr>
                <td><label for="name">Name:</label></td>
                <td>
                    <input type="text" id="name" name="name" 
                           value="<?= htmlspecialchars($name); ?>" maxlength="50">
                    <span class="error"><?= $nameErr; ?></span>
                </td>
            </tr>
            <tr>
                <td><label for="phone">Phone:</label></td>
                <td>
                    <input type="text" id="phone" name="phone" 
                           value="<?= htmlspecialchars($phone); ?>" maxlength="15">
                    <span class="error"><?= $phoneErr; ?></span>
                </td>
            </tr>
            <tr>
                <td><label for="address">Address:</label></td>
                <td>
                    <input type="text" id="address" name="address" 
                           value="<?= htmlspecialchars($address); ?>" maxlength="50">
                    <span class="error"><?= $addressErr; ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="form-actions">
                    <button type="submit" class="submit-button">Add Student</button>
                    <a href="liststudents.php" class="button-secondary">Cancel</a>
                </td>
            </tr>
        </table>
=======
    <h3><a href="liststudents.php">Back to Students</a></h3>

    <!-- SUCCESS/ERROR MESSAGE -->
    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <!-- ADD STUDENT -->
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
>>>>>>> f0c7a39111c8d69bcfd07a62a69f26345449075b
    </form>

    <?php if ($recentStudent): ?>
    <div class="recent-addition">
        <h2>Most Recently Added Student</h2>
        <table class="data-table">
            <tr>
                <th>Rocket ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Added Date</th>
            </tr>
            <tr>
                <td><?= htmlspecialchars($recentStudent['rocketid']); ?></td>
                <td><?= htmlspecialchars($recentStudent['name']); ?></td>
                <td><?= htmlspecialchars($recentStudent['phone']); ?></td>
                <td><?= htmlspecialchars($recentStudent['address']); ?></td>
                <td><?= GetFormattedDate($recentStudent['create_dt']); ?></td>
            </tr>
        </table>
    </div>
    <?php endif; ?>
    
    <h3><a href="liststudents.php">Back to Students</a></h3>
</body>
</html>