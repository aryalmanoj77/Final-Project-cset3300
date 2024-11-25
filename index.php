<?php
  session_start();
?>

<?php require('inc-stdmeta.php'); ?>

<?php
  // check session variable
  if (isset($_SESSION['valid_user']))
  {
    echo '<p>You are logged in as '.$_SESSION['valid_user'].'</p>';
echo '  <head>

    <title>Index</title>
  </head>
  <body>
    <h1>Index</h1>
    <h3><a href="books/listbooks.php">Books</a></h3>
    <h3><a href="students/liststudents.php">Students</a></h3>
    <h3><a href="home/logout.php">Logout</a></h3>
  </body>
</html> 
';  }
  else
  {
    echo '<p>You are not logged in.</p>';
    echo '<p>Only logged in members may see this page.</p>';
   echo' <a href="Home/login.php">Go back to Login Page</a>';
  }


?>
