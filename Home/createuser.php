<?php
  require('../isauthenticated.php');
  
  $username = $password = $confirmPassword = "";
  $usernameErr = $passwordErr = $confirmPasswordErr = "";
  $success = false;

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty($_POST["username"])) {
      $usernameErr = "Username is required";
    } else {
      $username = CleanInput($_POST["username"]);
      if (strlen($username) > 30) {
        $usernameErr = "Username must be 30 characters or less";
      } else {
        // Check if username already exists
        $inifile = parse_ini_file("../myproperties.ini");
        $conn = mysqli_connect($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"])
                or die("Connection failed: " . mysqli_connect_error());
                
        $sql = "SELECT username FROM user_authentication WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
          $usernameErr = "Username already exists";
        }
        $stmt->close();
      }
    }
    
    // Validate password
    if (empty($_POST["password"])) {
      $passwordErr = "Password is required";
    } else {
      $password = $_POST["password"];
      // Add password requirements as needed
      if (strlen($password) < 8) {
        $passwordErr = "Password must be at least 8 characters long";
      }
    }
    
    // Validate confirm password
    if (empty($_POST["confirmPassword"])) {
      $confirmPasswordErr = "Please confirm password";
    } else {
      $confirmPassword = $_POST["confirmPassword"];
      if ($password !== $confirmPassword) {
        $confirmPasswordErr = "Passwords do not match";
      }
    }
    
    // If no errors, create user
    if (empty($usernameErr) && empty($passwordErr) && empty($confirmPasswordErr)) {
      // Hash the password
      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
      
      $sql = "INSERT INTO user_authentication (username, passwordhash) VALUES (?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $username, $passwordHash);
      
      if ($stmt->execute()) {
        $success = true;
        $username = $password = $confirmPassword = "";
      } else {
        echo "Error creating user: " . $conn->error;
      }
      
      $stmt->close();
      $conn->close();
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
    <title>Create User</title>
  </head>
  <body>
    <h1>Create User</h1>
    <h3>CSET Department Student Library</h3>
    
    <?php if ($success): ?>
      <p class="success">User successfully created.</p>
    <?php endif; ?>
    
    <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <table>
        <tr>
          <td><label for="username">Username:</label></td>
          <td>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username); ?>" maxlength="30">
            <span class="error"><?= $usernameErr; ?></span>
          </td>
        </tr>
        <tr>
          <td><label for="password">Password:</label></td>
          <td>
            <input type="password" id="password" name="password">
            <span class="error"><?= $passwordErr; ?></span>
          </td>
        </tr>
        <tr>
          <td><label for="confirmPassword">Confirm Password:</label></td>
          <td>
            <input type="password" id="confirmPassword" name="confirmPassword">
            <span class="error"><?= $confirmPasswordErr; ?></span>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <input type="submit" value="Create User">
          </td>
        </tr>
      </table>
    </form>
    
    <h3><a href="../index.php">Back to Home</a></h3>
  </body>
</html>