<?php
  //Before anything, kick the user to the home page if they're already logged in.
  session_start();
  if(isset($_SESSION['valid_user'])){
    header('Location: ../index.php');
    exit();
  }

  //User inputted username.
  $username = "";
  //User inputted password.
  $password = "";
  //Any input error populates these.
  $usernameErr = $passwordErr = "";
  //Set when a successful authentication has occurred.
  $succ = false;

  //Check username and password inputs and authenticate user if they pass.
  if($_SERVER['REQUEST_METHOD']=="POST"){
    //First, set errors for any empty fields.
    if(empty($_POST['username'])){
      $usernameErr = "Please enter username.";
    }
    if(empty($_POST['password'])){
      $passwordErr = "Please enter password.";
    }
    //Second, populate $username and $password if $usernameErr and $passwordErr are not empty, respectively.
    if(empty($usernameErr)){
      $username = CleanInput($_POST['username']);
    }
    if(empty($passwordErr)){
      $password = CleanInput($_POST['password']);
    }
    //Third, if $usernameErr is still empty, then check that we get a database hit.
    if(empty($usernameErr)){
      $inifile = parse_ini_file("../myproperties.ini");
      $conn = mysqli_connect($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"])
              or die("Connection failed:".mysqli_connect_error());
      $stmt = $conn->prepare("SELECT`passwordhash`FROM`user_authentication`WHERE`username`= ? ");
      $stmt->bind_param("s",$username);
      $stmt->execute();
      $result = $stmt->get_result();
      if($result->num_rows!=1){
        $usernameErr = $passwordErr = "Invalid username or password.";
      }else{
        $user = $result->fetch_assoc();
        //Fourth, if $passwordErr is still empty, then compare passwords.
        if(empty($passwordErr)){
          if(password_verify($password,$user['passwordhash'])){
            $succ=true;
          }else{
            $usernameErr = $passwordErr = "Invalid username or password.";
          }
        }
      }
      $conn->close();
    }
  }
  //Fifth, set authenticated user session if no errors and kick user to homepage.
  if($succ){
    $_SESSION['valid_user'] = $username;
    header('Location: ../index.php');
    exit();
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
    <?php require('..\inc-stdmeta.php'); ?>
    <title>Login Page</title>
  </head>
  <body>
    <h1>Login Page</h1>
    <h3>CSET Department Student Library</h3>
    <form class="field-field" method="POST" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
      <table class>
        <tr>
          <td class="field-label">Username:</td>
          <td><input class="textbox" type="text" name="username" value="<?=$username?>" size="30"/></td>
          <td><span class="error"><?=$usernameErr?></span></td>
        </tr>
        <tr>
          <td class="field-label">Password:</td>
          <td><input class="textbox" type="password" name="password" value="<?=$password?>" size="30"/></td>
          <td><span class="error"><?=$passwordErr?></span></td>
        </tr>
        <tr>
          <td class="field-label">Login:</td>
          <td><button class="change-button" type="submit" name="login">Login</button></td>
        </tr>
      </table>
    </form>
  </body>
</html>