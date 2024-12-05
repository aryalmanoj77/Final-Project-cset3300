<?php
  require('../isauthenticated.php');
  
  $rocketid = $name = $phone = $address = "";
  $nameErr = $phoneErr = $addressErr = "";
  $success = $error = "";
  $student = null;

  // Database connection
  $inifile = parse_ini_file("../myproperties.ini");
  $conn = mysqli_connect($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"])
          or die("Connection failed: " . mysqli_connect_error());

  // Check if rocketid is provided
  if(!isset($_GET['rocketid']) && !isset($_POST['rocketid'])) {
    header("Location: liststudents.php");
    exit();
  }

  // Get student information if rocketid is provided
  $rocketid = isset($_GET['rocketid']) ? CleanInput($_GET['rocketid']) : CleanInput($_POST['rocketid']);
  
  $sql = "SELECT rocketid, name, phone, address, active FROM student WHERE rocketid = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $rocketid);
  $stmt->execute();
  $result = $stmt->get_result();
  $student = $result->fetch_assoc();
  
  if($student) {
    $name = $student['name'];
    $phone = $student['phone'];
    $address = $student['address'];
    $active = $student['active'];
  } else {
    // If student not found, redirect to list
    header("Location: liststudents.php");
    exit();
  }
  $stmt->close();

  // Handle form submission
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
    
    // Get active status
    $active = isset($_POST["active"]) ? 1 : 0;
    
    // If no errors, update database
    if (empty($nameErr) && empty($phoneErr) && empty($addressErr)) {
      $sql = "UPDATE student SET name = ?, phone = ?, address = ?, active = ? WHERE rocketid = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssis", $name, $phone, $address, $active, $rocketid);
      
      if ($stmt->execute()) {
        $success = "Student information updated successfully.";
      } else {
        $error = "Error updating student information.";
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
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php require('../inc-stdmeta.php'); ?>
    <title>Edit Student | CSET Library</title>
  </head>
  <body>
    <h1>Edit Student</h1>
    <h3>CSET Department Student Library</h3>
    
    <?php if ($success): ?>
      <div class="success-message"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
      <div class="error-message"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <input type="hidden" name="rocketid" value="<?= htmlspecialchars($rocketid); ?>">
      <table>
        <tr>
          <td><label for="rocketid">Rocket ID:</label></td>
          <td>
            <input type="text" value="<?= htmlspecialchars($rocketid); ?>" disabled>
          </td>
        </tr>
        <tr>
          <td><label for="name">Name:</label></td>
          <td>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name); ?>" maxlength="50">
            <span class="error"><?= $nameErr; ?></span>
          </td>
        </tr>
        <tr>
          <td><label for="phone">Phone:</label></td>
          <td>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone); ?>" maxlength="15">
            <span class="error"><?= $phoneErr; ?></span>
          </td>
        </tr>
        <tr>
          <td><label for="address">Address:</label></td>
          <td>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($address); ?>" maxlength="50">
            <span class="error"><?= $addressErr; ?></span>
          </td>
        </tr>
        <tr>
          <td><label for="active">Active:</label></td>
          <td>
            <input type="checkbox" id="active" name="active" <?= $active ? "checked" : ""; ?>>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <input type="submit" value="Update Student">
            <a href="liststudents.php" class="button-secondary">Cancel</a>
          </td>
        </tr>
      </table>
    </form>

    <?php if ($student): ?>
    <div class="checkout-history">
      <h2>Current Checkouts</h2>
      <?php
        // Get current checkouts
        $sql = "SELECT c.*, b.title FROM checkout c 
                JOIN book b ON c.bookid = b.bookid 
                WHERE c.rocketid = ? AND c.return_date IS NULL 
                ORDER BY c.checkout_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $rocketid);
        $stmt->execute();
        $checkouts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      ?>
      
      <?php if (!empty($checkouts)): ?>
      <table class="checkout-table">
        <tr>
          <th>Book Title</th>
          <th>Checkout Date</th>
          <th>Due Date</th>
          <th>Action</th>
        </tr>
        <?php foreach($checkouts as $checkout): ?>
        <tr>
          <td><?= htmlspecialchars($checkout['title']) ?></td>
          <td><?= htmlspecialchars($checkout['checkout_date']) ?></td>
          <td><?= htmlspecialchars($checkout['promise_date']) ?></td>
          <td>
            <a href="../checkouts/return.php?checkoutid=<?= $checkout['checkoutid'] ?>" 
               class="button-secondary">Return Book</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
      <p>No current checkouts.</p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <h3><a href="liststudents.php">Back to Students</a></h3>
  </body>
</html>

<?php
$conn->close();
?>