<?php
  session_start(); 
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate'])) {
      require('../dbconnection.php'); // Insert DBC

      $student_id = $_POST['student_id']; // Get student ID from form
      $query = "UPDATE students SET active = 0 WHERE id = ?";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("i", $student_id);

      $message = $stmt->execute()
          ? "Student account has been deactivated successfully."
          : "Error: Unable to deactivate student account.";

      $stmt->close();
      $conn->close();
  }
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

    <!-- Success/Error message -->
    <?php if (!empty($message)): ?>
      <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <!-- Deactivate student account -->
    <?php if (isset($_GET['id'])): ?>
    <form action="editstudent.php" method="post">
      <p>
        <label for="student_id">Student ID:</label>
        <input type="text" name="student_id" id="student_id" size="30" value="<?php echo htmlspecialchars($_GET['id']); ?>" readonly />
      </p>
      <p>
        <button type="submit" name="deactivate">Deactivate Student Account</button>
      </p>
    </form>
    <?php else: ?>
      <p>No student selected for editing.</p>
    <?php endif; ?>

    <!-- Link back to liststudents.php -->
    <p><a href="liststudents.php">Back to Students</a></p>
  </body>
</html>
