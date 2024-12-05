<?php
  //Always check authentication.
  require('../isauthenticated.php');

  //Username from $_SESSION['valid_user'];
  $username = "";
  //User inputted passwords.
  $current = $new = $confirm = "";
  //The new hashed password.
  $hash = "";
  //Any input error populates these.
  $currentErr = $newErr = $confirmErr = "";
  //Set when a successful database insert has occurred.
  $succ = false;

  //Check $_SESSION more closely and password inputs and insert into database if they pass.
  if($_SERVER['REQUEST_METHOD']=="POST" && !empty($_SESSION['valid_user'])){
    $username = CleanInput($_SESSION['valid_user']);
    //First, set errors for any empty fields.
    if(empty($_POST['current_password'])){
      $currentErr = "Please enter current password.";
    }
    if(empty($_POST['new_password'])){
      $newErr = "Please enter new password.";
    }
    if(empty($_POST['confirm_password'])){
      $confirmErr = "Please confirm new password.";
    }
    //Second, if $currentErr is still empty, then set $current and check against database.
    if(empty($currentErr)){
      $current = CleanInput($_POST['current_password']);
      $inifile = parse_ini_file("../myproperties.ini");
      $conn = mysqli_connect($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"])
              or die("Connection failed:".mysqli_connect_error());
      $stmt = $conn->prepare("SELECT`passwordhash`FROM`user_authentication`WHERE`username`= ? ");
      $stmt->bind_param("s",$username);
      $stmt->execute();
      $result = $stmt->get_result();
      if($result->num_rows!=1){
        $currentErr = "ERROR: Could not compare to database.";
      }else{
        $user = $result->fetch_assoc();
        if(!password_verify($current,$user['passwordhash'])){
          $currentErr = "Current password incorrect.";
        }
      }
      $conn->close();
    }
    //Third, if $newErr is still empty, then set $new and check for password requirements.
    if(empty($newErr)){
      $new = CleanInput($_POST['new_password']);
      if(strlen($new)<8){
        $newErr = "Password should be at least 8 characters long.";
      }
    }
    //Fourth, if $confirmErr is still empty, then set $confirm.
    if(empty($confirmErr)){
      $confirm = CleanInput($_POST['confirm_password']);
    }
    //Fifth, if $newErr and $confirmErr are still empty, then compare $new and $confirm, setting hash upon equality.
    if(empty($newErr) && empty($confirmErr)){
      if($new==$confirm){
        $hash = password_hash($new,PASSWORD_DEFAULT);
      }else{
        $confirmErr = "Re-typed password doesn't match new password.";
      }
    }
    //Sixth, if all errors are empty, attempt a database update.
    if(empty($currentErr) && empty($newErr) && empty($confirmErr)){
      $inifile = parse_ini_file("../myproperties.ini");
      $conn = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
              or die("Connection failed:".mysqli_connect_error());
      $stmt = $conn->prepare("UPDATE`user_authentication`SET`passwordhash`= ? WHERE`username`= ? ");
      $stmt->bind_param("ss",$hash,$username);
      $succ = $stmt->execute();
      if(!$succ){
        $currentErr = "ERROR: Could not update password.";
      }
      $conn->close();
    }
  }

  //FUNCTIONS
  //Distrust all user input.
  function CleanInput($data){
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
    <title>Password Change Page</title>
  </head>
  <body>
    <h1>Change Password</h1>
    <h3>CSET Department Student Library</h3>
    <h2><a href="../index.php">Back to Home</a></h2>
    <?php if($succ): ?>
    <h3>Password updated for the current user <?=CleanInput($_SESSION['valid_user'])?>.</h3>
    <?php else: ?>
    <p style="font-weight: bold">Change password for the current user <?=CleanInput($_SESSION['valid_user'])?>.</p>
    <form class="field-field" method="POST" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
      <table>
        <tr>
          <td class="field-label">Current Password:</td>
          <td><input class="textbox" type="password" name="current_password" value="<?=$current?>" size="30"/></td>
          <td><span class="error"><?=$currentErr?></span></td>
        </tr>
        <tr>
          <td class="field-label">New Password:</td>
          <td><input class="textbox" type="password" name="new_password" value="<?=$new?>" size="30"/></td>
          <td><span class="error"><?=$newErr?></span></td>
        </tr>
        <tr>
          <td class="field-label">Confirm New Password:</td>
          <td><input class="textbox" type="password" name="confirm_password" value="<?=$confirm?>" size="30"/></td>
          <td><span class="error"><?=$confirmErr?></span></td>
        </tr>
        <tr>
          <td class="field-label">Change Password:</td>
          <td><button class="change-button" type="submit" name="change">Change</button></td>
        </tr>
      </table>
    </form>
    <?php endif; ?>
    <h2><a href="../index.php">Back to Home</a></h2>
  </body>
</html>