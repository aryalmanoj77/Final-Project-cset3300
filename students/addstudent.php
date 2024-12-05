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
    }
    return "No Date Found";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php require('../inc-stdmeta.php'); ?>
    <title>Add Student</title>
  </head>
  <body>
    <h1>Add Student</h1>
    <h3>CSET Department Student Library</h3>
    <h2><a href="../students/liststudents.php">Back to Students</a></h2>
    <?php if ($success): ?>
    <h3 style="margin-bottom: 0.25em">Successfully Added Student:</h3>
    <table style="margin-bottom: 0.25em">
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
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="error-message"><?= $error ?></div>
    <?php endif; ?>
    <form class="field-field" method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <table>
        <tr>
          <td class="field-label">Rocket ID:</td>
          <td><input class="textbox" type="text" name="rocketid" value="<?= htmlspecialchars($rocketid); ?>" maxlength="10"></td>
          <td><span class="error"><?= $rocketidErr; ?></span></td>
        </tr>
        <tr>
          <td class="field-label">Name:</td>
          <td><input class="textbox" type="text" name="name" value="<?= htmlspecialchars($name); ?>" maxlength="50"></td>
          <td><span class="error"><?= $nameErr; ?></span></td>
        </tr>
        <tr>
          <td class="field-label">Phone:</td>
          <td><input class="textbox" type="text" name="phone" value="<?= htmlspecialchars($phone); ?>" maxlength="15"></td>
          <td><span class="error"><?= $phoneErr; ?></span></td>
        </tr>
        <tr>
          <td class="field-label">Address:</td>
          <td><input class="textbox" type="text" name="address" value="<?= htmlspecialchars($address); ?>" maxlength="50"></td>
          <td><span class="error"><?= $addressErr; ?></span></td>
        </tr>
        <tr>
          <td class="field-label">Add Student:</td></td>
          <td><button class="change-button" type="submit" name="add">Add</button></td>
        </tr>
      </table>
    </form>
    <h2><a href="../students/liststudents.php">Back to Students</a></h2>
</body>
</html>