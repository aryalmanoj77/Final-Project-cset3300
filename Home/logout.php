<?php
  //Store to test if they *were* logged in.
  session_start();
  $old_user = "";
  if(isset($_SESSION['valid_user'])){
    $old_user = $_SESSION['valid_user'];
    unset($_SESSION['valid_user']);
  }
  session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
  <?php require('../inc-stdmeta.php'); ?>
    <title>Log Out</title>
  </head>
  <body>
    <h1>Log Out</h1>
    <h3>CSET Department Student Library</h3>
  <?php if(!empty($old_user)): ?>
    <h3>User <?=htmlspecialchars($old_user);?> has been logged out.</h3>
  <?php else: ?>
    <h3>No user logged in to log out.</h3>
  <?php endif; ?>
    <h2><a href="../home/login.php">Back to Login Page</a></h2>
  </body>
</html>