<?php
  //This is just copied for now and hasn't been tested.
  //Start a session, check that the user is stored in the session.
  //If not, redirect to the login page.
  session_start();
  if(!isset($_SESSION['valid_user'])){
    header('Location: home/login.php');
    exit();
  }
?>