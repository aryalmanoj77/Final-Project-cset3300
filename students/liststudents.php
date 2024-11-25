<?php
  session_start();
  //Show only active, only inactive, or both.
  $filteractive = "";
  //Column to order by.
  $sortcol = "";
  //Ascending or descending for sortcol.
  $sortdir = "";

  //Check for sort ordering criteria
  if($_SERVER['REQUEST_METHOD']=="GET"){
    //Check column sorting first and populate variable.
    if(isset($_GET['sortcol'])){
      $sortcol .= "ORDER BY";
      switch($_GET['sortcol']){
        case 'name':
          $sortcol .= "`name`";
          break;
        case 'phone':
          $sortcol .= "`phone`";
          break;
        case 'address':
          $sortcol .= "`address`";
          break;
        case 'rocketid':
        default:
          $sortcol .= "`rocketid`";
      }
      //Check sorting direction if there's a sortcol and populate.
      if(isset($_GET['sortdir'])){
        switch($_GET['sortdir']){
          case 'asc':
            $sortdir = "ASC";
            break;
          case 'desc':
          default:
            $sortdir = "DESC";
        }
      }
    }
    //Second, filter active status.
    if(isset($_GET['active'])){
      $filteractive .= "WHERE `active`";
      switch($_GET['active']){
        case 'active':
          $filteractive .= "IS TRUE";
          break;
        case 'inactive':
          $filteractive .= "IS FALSE";
          break;
        case 'both':
        default:
          $filteractive = "";
      }
    }
  }

  //Fetch associative array.
  $inifile = parse_ini_file("../myproperties.ini");
  $conn = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
          or die("Connection failed:" . mysqli_connect_error());
	//$sql = "SELECT `name`, `email`, `phone`, `bgroup`, `id` FROM `users` $sortcol $sortdir";
  $sql = "SELECT`rocketid`,`name`,`phone`,`address`,`active`";
  $sql .= "FROM`student`";
  $sql .= "$filteractive $sortcol $sortdir";
  $resultset = mysqli_query($conn,$sql);
  $students = mysqli_fetch_all($resultset,MYSQLI_ASSOC);

  //Fill in NULLS.
  foreach($students as &$student){
    foreach($student as &$key){
      if(is_null($key)){
        $key = 'None Listed';
      }
    }
    unset($key);
  }
  unset($student);

  //FUNCTIONS
  //Returns a modified version of GET for URL purposes.
  function ModifiedGET($key,$value){
    $tempGET = array_merge([], $_GET);
    //Check for same keys first and modify/add as necessary.
    foreach($_GET as $GETkey=>$GETvalue){
      //Catch that we're trying to sort on the same column as before.
      if(($GETkey==$key) && ($key=="sortcol") && ($GETvalue==$value)){
        //If sort direction isn't set, then it's defaulted to ascending, so set to descending.
        if(!isset($tempGET['sortdir'])){
          $tempGET['sortdir'] = "desc";
        }
        else{ //If it is set, then we're swapping between the two.
          switch($tempGET['sortdir']){
            case 'asc':
              $tempGET['sortdir'] = "desc";
              break;
            case 'desc':
            default:
              $tempGET['sortdir'] = "asc";
          }
        }
      }
    }
    //Checks and modifications done, now insert new keyvalues.
    $tempGET[$key] = $value;
    return $tempGET;
  }
  //So that the URL doesn't keep forgetting previous GETs.
  function RepopulateUrl($key,$value){
    //Get a modified version of GET.
    $tempGET = ModifiedGET($key,$value);
    //Now populate URL.
    $urlender = "liststudents.php?";
    $urlender .= http_build_query($tempGET);
    return $urlender;
  }
  //Return what kind of filtering the active criteria is doing.
  function KindActive(){
    if((isset($_GET['active']))){
      switch($_GET['active']){
        case 'active':
          return("active");
        case 'inactive':
          return("inactive");
        case 'both':
        default:
          return("both");
      }
    }
    else{
      return("both");
    }
  }
?>

<!DOCTYPE html>
<html>
  <head>
  <?php require('../inc-stdmeta.php'); ?>
    <title>Students</title>
  </head>
  <body>
    <h1>Students</h1>
    <form class="radio-field" method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <?php foreach($_GET as $key=>$value): ?>
    <?php if(!($key=="active")): ?>
      <input type="hidden" name="<?= $key; ?>" value="<?= $value; ?>">
    <?php endif; ?>
    <?php endforeach; ?>
      <table>
        <tr>
          <td>Active:</td>
          <td><input type="radio" name="active" value="both" <?php if(KindActive()=="both") echo "checked" ?>>Both</td>
          <td><input type="radio" name="active" value="active" <?php if(KindActive()=="active") echo "checked" ?>>Active</td>
          <td><input type="radio" name="active" value="inactive" <?php if(KindActive()=="inactive") echo "checked" ?>>Inactive</td>
        </tr>        
        <tr><td><input class="submit-button" type="submit" value="Submit"></td></tr>
      </table>
      <br/>
    </form>
    <table>
      <tr>
        <th><a href="addstudent.php">Add Student</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","rocketid"); ?>">Rocket ID</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","name"); ?>">Name</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","phone"); ?>">Phone</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","address"); ?>">Address</a></th>
      </tr>
    <?php foreach($students as $student): ?>
    <?php if(!$student['active']): ?>
      <tr class="grayed">
    <?php else: ?>
      <tr>
    <?Php endif; ?>
        <td><a href="editstudent.php?rocketid=<?= $student['rocketid']; ?>">Edit Student</a></td>
        <td><?= htmlspecialchars($student['rocketid']); ?></td>
        <td><?= htmlspecialchars($student['name']); ?></td>
        <td><?= htmlspecialchars($student['phone']); ?></td>
        <td><?= htmlspecialchars($student['address']); ?></td>
      </tr>
    <?php endforeach; ?>
    </table>
    <h3><a href="../index.php">Back to Index</a></h3>
    <h3><a href="addstudent.php">Add a Student</a></h3>
    <h3><a href="editstudent.php">Edit Student</a></h3>
  </body>
</html>