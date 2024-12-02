<?php
  //Always check authentication.
  require('../isauthenticated.php');
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php require('../inc-stdmeta.php'); ?>
    <title>Homepage</title>
  </head>
  <body>
    <h1>Homepage</h1>
    <h3>CSET Department Student Library</h3>
    <h3>Currently logged in as <?=htmlspecialchars($_SESSION['valid_user']);?>.</h3>
    <h2><a href="../checkouts/listcheckouts.php">Checkouts</a></h2>
    <h2><a href="../books/listbooks.php">Books</a></h2>
    <h2><a href="../students/liststudents.php">Students</a></h2>
    <h2><a href="../home/changepassword.php">Change Password</a></h2>
    <h2><a href="../home/createuser.php">Create User</a></h2>
    <h2><a href="../home/logout.php">Logout</a></h2>
  </body>
</html>