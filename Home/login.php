
<?php require('..\inc-stdmeta.php');
 ?>
<?php 
session_start();
if (isset($_POST['userid']) && isset($_POST['password'])) {
  // if the user has just tried to log in
  $userid = $_POST['userid'];
  $password = $_POST['password'];
  $isAuthenticated = false;

  // Password hash
 $hash = password_hash($password, PASSWORD_DEFAULT);

  // TEMPORARY:  Need this to seed the database.  Then comment out again.
 // echo '<br/>userid:' . $userid;
  //echo '<br/>hash:' . $hash;


  // Connect to the database using myproperties.ini
  $inifile = parse_ini_file("../myproperties.ini");
  $conn = new mysqli($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"]);

  $isAuthenticated = false;  // Assume not authenticated
} 
// Check if the user submitted login form
if (isset($_POST['userid']) && isset($_POST['password'])) {
    $userid = $_POST['userid'];
    $password = $_POST['password'];

    // Prepare statement to fetch user data
    $stmt = $conn->prepare("SELECT passwordhash FROM user_authentication WHERE username = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verify password if user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['passwordhash'])) {
            $isAuthenticated = true;  // Successful authentication
        }
    }

    $stmt->close();  // Close the statement
    $conn->close();  // Close the connection

    // Set session based on authentication status
    if ($isAuthenticated) {
        $_SESSION['valid_user'] = $userid;
    } else {
        unset($_SESSION['valid_user']);
    }
}
?>

<h1>Home Page</h1>
<?php
  if (isset($_SESSION['valid_user'])) {
     echo '<p>You are logged in as: '.$_SESSION['valid_user'].' <br />';
     echo '<a href="logout.php">Log out</a></p>';
  }
  else if (isset($userid)) {
      // if they've tried and failed to log in
      echo '<p>Could not log you in.  Bad username or password?</p>';
  }
  else {
      // they have not tried to log in yet or have logged out
      echo '<p>You are not logged in.</p>';
  }    
?>

<html>
  <head>
    <title>Login Page</title>
    <style type="text/css">
      label {
         width: 125px;
         float: left;
         text-align: left;
         font-weight: bold;
      }
      input {
         border: 1px solid #000;
         padding: 3px;
      }
      button {
         margin-top: 12px;
      }
    </style>
  </head>
  <body>
    <?php if (!isset($_SESSION['valid_user'])): ?>
    <form action="login.php" method="post">
      <p>
        <label for="userid">UserID:</label>
        <input type="text" name="userid" id="userid" size="30"/>
      </p>
      <p>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" size="30"/>
      </p>   
      <button type="submit" name="login">Login</button>
    </form>
    <?php endif; ?>
    <p><a href="..\index.php">Go to HomePage</a></p>

  </body>
  
</html>
      