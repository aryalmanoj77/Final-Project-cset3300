<?php
  //Always check authentication.
  require('../isauthenticated.php');

  //The associative array for all checked out books.
  $checkouts;
  //Single row associative array for the latest return.
  $latest;
  //Values for the selected book from database.
  $sel_checkoutid = $sel_title = $sel_name = "";
  //The checkoutid for the selected book from GET or POST.
  $checkoutid = "";
  //The latest successfully returned book.
  $latestid = "";
  //Column to order by.
  $sortcol = "";
  //Ascending or descending for sortcol.
  $sortdir = "";
  //User inputted filter string.
  $filtcol = "";
  //The column to filter inputted filter string on.
  $filtstr = "";
  //Combined filters for filtcol and filtstr.
  $filter = "";
  //Is set when we need to prepare the checkout sql query.
  $filtprep = false;
  //Error message for a failed return.
  $returnErr = "";
  //Set when a successful database update has occurred and stays (from GET) until a consecutive one fails.
  $succ = false;

  //FIRST, prioritize $_POST, which sends a checkout request.
  if($_SERVER['REQUEST_METHOD']=="POST"){
    //First, clean and set $checkoutid variable if not empty.
    if(empty($_POST['checkoutid'])){
      $returnErr = "Incomplete selection.";
    }else{
      $checkoutid = CleanInput($_POST['checkoutid']);
    }
    //Second, check that $checkoutid variablbe is not empty.
    if(empty($checkoutid)){
      $returnErr = "Incomplete selection.";
    }else{ //Third, validate data for our return request via a database query, checking returnability.
      $inifile = parse_ini_file("../myproperties.ini");
      $conn    = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
                 or die("Connection failed:".mysqli_connect_error());
      $checksql = "SELECT`checkoutid`,`promise_date`,`return_date`";
      $checksql .= "FROM`checkout`";
      $checksql .= "WHERE`checkoutid`= ? "; //Checkout needs to have this id.
      $checksql .= "AND(`return_date`IS NULL AND`promise_date`IS NOT NULL)"; //And be checked out.
      $checkstmt = $conn->prepare($checksql);
      $checkstmt->bind_param("s",$checkoutid);
      $checkstmt->execute();
      $checkresult = $checkstmt->get_result();
      if($checkresult->num_rows!=1){
        $returnErr .= "Selected Checkout ID is not valid for checkout. ";
      }else{ //To catch potential wildcards someone may have POSTed for checkoutid.
        $check = $checkresult->fetch_assoc();
        if($check['checkoutid']!=$checkoutid){
          $returnErr .= "Selected Checkout ID is not valid for checkout. ";
        }
      }
      $conn->close();
    }
    //Fourth, if $returnErr is still empty, attempt to update the checkout in the database.
    if(empty($returnErr)){
      $returneddate = GetTodaysDate();
      $inifile = parse_ini_file("../myproperties.ini");
      $conn    = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
                 or die("Connection failed:".mysqli_connect_error());
      $returnsql =  "UPDATE`checkout`SET`return_date`='$returneddate' WHERE`checkoutid`= ? ";
      $returnstmt = $conn->prepare($returnsql);
      $returnstmt->bind_param("s",$checkoutid);
      $succ = $returnstmt->execute();
      $conn->close();
      if(!$succ){
        $checkoutErr = "ERROR: Could not issue checkout.";
      }
    }
    //Fifth, if return could not be issued, reload page with cached $_POST values so they show up in $_GET.
    if(!$succ){
      $newGET = array_merge([], $_POST);
      //Carry over errors and success status.
      $newGET['returnErr'] = CleanInput($returnErr);
      $newGET['succ'] = CleanInput($succ);
      //Remove the last successful return from GET, so it doesn't get displayed.
      if(isset($newGET['latestid'])){
        unset($newGET['latestid']);
      }
      $urlender = "return.php?";
      $urlender .= http_build_query($newGET);
      header("Location: $urlender");
			exit();
    }
    //Sixth, if return could be issued, reload page with just the success and the latest returned id and a cleaned $_GET.
    if($succ){
      $cleanedGET['succ'] = CleanInput($succ);
      $cleanedGET['latestid'] = CleanInput($checkoutid);
      $urlender = "return.php?";
      $urlender .= http_build_query($cleanedGET);
      header("Location: $urlender");
      exit();
    }
  }

  //SECOND, check $_GET for extra values sent over prior POSTs and set variables.
  if($_SERVER['REQUEST_METHOD']=="GET"){
    if(isset($_GET['returnErr'])){
      $returnErr = CleanInput($_GET['returnErr']);
    }
    if(isset($_GET['succ'])){
      $succ = CleanInput($_GET['succ']);
    }
    if(isset($_GET['latestid'])){
      $latestid = CleanInput($_GET['latestid']);
    }
  }

  //THIRD, fetch the latest return if last return was successful and the latest returned id is not empty.
  if($succ && !empty($latestid)){
    $latestid = CleanInput($_GET['latestid']);
    $inifile = parse_ini_file("../myproperties.ini");
    $conn    = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
               or die("Connection failed:".mysqli_connect_error());
    $l_sql =  "SELECT";
    $l_sql .= "`checkoutid`,";
    $l_sql .= "`bookid`,`title`,`author`,`publisher`,";
    $l_sql .= "`rocketid`,`name`,`phone`,`address`,";
    $l_sql .= "`checkout_date`,`promise_date`,`return_date`";
    $l_sql .= "FROM`master_checkout_query`";
    $l_sql .= "WHERE`checkoutid`= ? ";
    $l_prepsql = $conn->prepare($l_sql);
    $l_prepsql->bind_param("s",$latestid);
    $l_prepsql->execute();
    $l_result = $l_prepsql->get_result();
    if($l_result->num_rows==1){
      $latest = $l_result->fetch_assoc();
    }
    $conn->close();
  }

  //FOURTH, fill selection field values when last checkout wasn't successful or if REQUEST_METHOD is GET.
  if(!$succ || $_SERVER['REQUEST_METHOD']=="GET"){
    //First, check that $_GET['checkoutid'] isn't empty.
    if(!empty($_GET['checkoutid'])){
      //Second, fetch from database.
      $checkoutid = CleanInput($_GET['checkoutid']);
      $inifile = parse_ini_file("../myproperties.ini");
      $conn    = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
                 or die("Connection failed:".mysqli_connect_error());
      $sel_c_sql =  "SELECT`checkoutid`,`title`,`name`";
      $sel_c_sql .= "FROM`master_checkout_query`";
      $sel_c_sql .= "WHERE`checkoutid`= ? ";
      $sel_c_prepsql = $conn->prepare($sel_c_sql);
      $sel_c_prepsql->bind_param("s",$checkoutid);
      $sel_c_prepsql->execute();
      $sel_c_result = $sel_c_prepsql->get_result();
      //Third, check that we get one array row.
      if($sel_c_result->num_rows==1){
        $sel_checkout = $sel_c_result->fetch_assoc();
        //Fourth, check matching again to catch potential wildcards someone may have sent.
        if($sel_checkout['checkoutid']==$checkoutid){
          $sel_checkoutid = htmlspecialchars($sel_checkout['checkoutid']);
          $sel_title = htmlspecialchars($sel_checkout['title']);
          $sel_name = htmlspecialchars($sel_checkout['name']);
        }
      }
      $conn->close();
    }
  }

  //FIFTH, check for checkout sort/filter when last checkout wasn't successful or if REQUEST_METHOD is GET.
  if(!$succ || $_SERVER['REQUEST_METHOD']=="GET"){
    //First, populate sortcol variable if there's sorting.
    if(isset($_GET['sortcol'])){
      $sortcol .= "ORDER BY";
      switch($_GET['sortcol']){
        case 'bookid':
          $sortcol .= "`bookid`";
          break;
        case 'title':
          $sortcol .= "`title`";
          break;
        case 'author':
          $sortcol .= "`author`";
          break;
        case 'publisher':
          $sortcol .= "`publisher`";
          break;
        case 'rocketid':
          $sortcol .= "`rocketid`";
          break;
        case 'name':
          $sortcol .= "`name`";
          break;
        case 'phone':
          $sortcol .= "`phone`";
          break;
        case 'address':
          $sortcol .= "`address`";
          break;
        case 'checkout_date':
          $sortcol .= "`checkout_date`";
          break;
        case 'promise_date':
          $sortcol .= "`promise_date`";
          break;
        case 'checkoutid':
        default:
          $sortcol .= "`checkoutid`";
      }
      //Second, populate sortdir variable if there's sorting.
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
    //Third, check filtering for filtercolumns.
    if(isset($_GET['filtcol'])){
      switch($_GET['filtcol']){
        case 'checkoutid':
          $filtcol = "`checkoutid`";
          break;
        case 'bookid':
          $filtcol = "`bookid`";
          break;
        case 'title':
          $filtcol = "`title`";
          break;
        case 'author':
          $filtcol = "`author`";
          break;
        case 'publisher':
          $filtcol = "`publisher`";
          break;
        case 'rocketid':
          $filtcol = "`rocketid`";
          break;
        case 'name':
          $filtcol = "`name`";
          break;
        case 'phone':
          $filtcol = "`phone`";
          break;
        case 'address':
          $filtcol = "`address`";
          break;
        case 'checkout_date':
          $filtcol = "`checkout_date`";
          break;
        case 'promise_date':
          $filtcol = "`promise_date`";
          break;
        case '':
        default:
      }
      //Fourth, check filtering for filterstrings.
      if(!empty($filtcol) && isset($_GET['filtstr'])){
        $filtstr = CleanInput($_GET['filtstr']);
        if(!empty($filtstr) || $filtstr==0){
          //filterstring will need to be prepared.
          $filtstr = "%$filtstr%";
          $filter = "AND".$filtcol."LIKE ? ";
          $filtprep = true;
        }
      }
    }
  }

  //SIXTH, fetch the checkout associative array regardless of success or REQUEST_METHOD.
  if(true){
    $inifile = parse_ini_file("../myproperties.ini");
    $conn = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
            or die("Connection failed:".mysqli_connect_error());
    //Only grab checkouts that are active with checked out books.
    $c_sql =  "SELECT";
    $c_sql .= "`checkoutid`,";
    $c_sql .= "`bookid`,`title`,`author`,`publisher`,";
    $c_sql .= "`rocketid`,`name`,`phone`,`address`,";
    $c_sql .= "`checkout_date`,`promise_date`";
    $c_sql .= "FROM`master_checkout_query`";
    $c_sql .= "WHERE(`return_date`IS NULL AND`promise_date`IS NOT NULL)";
    $c_sql .= "$filter $sortcol $sortdir";
    if($filtprep){
      $c_prepsql = $conn->prepare($c_sql);
      $c_prepsql->bind_param("s",$filtstr);
      $c_prepsql->execute();
      $c_resultset = $c_prepsql->get_result();
      $checkouts = $c_resultset->fetch_all(MYSQLI_ASSOC);
    }else{
      $c_resultset = mysqli_query($conn,$c_sql);
      $checkouts = mysqli_fetch_all($c_resultset,MYSQLI_ASSOC);
    }
    $conn->close();
  }

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
        }else{ //If it is set, then we're swapping between the two.
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
    //Checks and modifications done, now insert/update new keyvalues.
    $tempGET[$key] = $value;
    return $tempGET;
  }
  //So that the URL doesn't keep forgetting previous GETs.
  function RepopulateUrl($key,$value){
    //Get a modified version of GET.
    $tempGET = ModifiedGET($key,$value);
    //Now populate URL.
    $urlender = "return.php?";
    $urlender .= http_build_query($tempGET);
    return $urlender;
  }
  //Cleans out all GETS except for succ, returnErr, and latestid if they exist.
  function ClearAll($success,$error,$id){
    $keyvalues;
    if(!empty($success) || $success==0){
      $keyvalues['succ'] = CleanInput($success);
    }
    if(!empty($error)){
      $keyvalues['returnErr'] = CleanInput($error);
    }
    if(!empty($id)){
      $keyvalues['latestid'] = CleanInput($id);
    }
    $urlender = "return.php?";
    if(!empty($keyvalues)){
      $urlender .= http_build_query($keyvalues);
    }
    return($urlender);
  }
  //Gets rid of the current selection.
  function ClearSelected($key){
    $tempGET = array_merge([], $_GET);
    foreach($_GET as $GETkey=>$GETvalue){
      if($GETkey==$key){
        unset($tempGET[$key]);
      }
    }
    $urlender = "return.php?";
    $urlender .= http_build_query($tempGET);
    return($urlender);
  }
  //Echoes values in an html input for a selection while checking for array existence.
  function GetInputSelected($key,$value){
    if($key=="checkoutid"){
      echo '<input class="textbox" type="text" style="font-weight: bold" name="checkoutid" value="Checkout ID: ';
      echo $value.'" size="20" disabled/>';
    }else if($key=="title"){
      echo '<input class="textbox" type="text" style="font-weight: bold" name="title" value="Book Title: ';
      echo $value.'" size="30" disabled/>';
    }else if($key=="name"){
      echo '<input class="textbox" type="text" style="font-weight: bold" name="name" value="Student Name: ';
      echo $value.'" size="30" disabled/>';
    }
  }
  //To remove filtering.
  function ClearFiltering(){
    $tempGET = array_merge([], $_GET);
    foreach($_GET as $key=>$value){
      if($key=="filtcol" || $key=="filtstr"){
        unset($tempGET[$key]);
      }
    }
    $urlender = "return.php?";
    $urlender .= http_build_query($tempGET);
    return($urlender);
  }
  //Return the current filterstring.
  function GetFilterString($key){
    if(isset($_GET[$key])){
      return(CleanInput($_GET[$key]));
    }
    return("");
  }
  //Return the current filtercolumn.
  function GetFilterColumn($key,$value){
    if(isset($_GET[$key])){
      if($_GET[$key]==$value){
        return('selected="selected"');
      }
    }
    return("");
  }
  //Gets today's date and returns it in the proper format.
  function GetTodaysDate(){
    date_default_timezone_set('America/Detroit');
    $date = date('Y-m-d');
    $date = date_create($date);
    return(date_format($date,"Y-m-d"));
  }
  //Returns strings as date formats all in the same format.
  function GetFormattedDate($string){
    if(!empty($string)){
      $date = strtotime($string);
      if($date!==false){
        return(date('Y-m-d',$date));
      }else{
        return(htmlspecialchars($string));
      }
    }
    return("No Date Found");
  }
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
    <title>Return Book</title>
  </head>
  <body>
    <h1>Return Book</h1>
    <h3>CSET Department Student Library</h3>
    <h2>
      <span class="h2-span"><a href="../checkouts/listcheckouts.php">Back to Checkouts</a></span>
      <span class="h2-span"><a href="../books/listbooks.php">Back to Books</a></span>
      <span class="h2-span"><a href="../students/liststudents.php">Back to Students</a></span>
    </h2>
    <?php if($succ && !empty($latest)): ?>
    <h3 style="margin-bottom: 0.25em">Successfully Returned Book:</h3>
    <table style="margin-bottom: 0.25em">
      <tr>
        <th>Checkout ID</th>
        <th>Book ID</th>
        <th>Book Title</th>
        <th>Book Author</th>
        <th>Book Publisher</th>
        <th>Rocket ID</th>
        <th>Student Name</th>
        <th>Student Phone</th>
        <th>Student Address</th>
        <th>Checkout Date</th>
        <th>Return By Date</th>
        <th>Date Returned</th>
      </tr>
      <tr>
        <td style="text-align: center"><?=htmlspecialchars($latest['checkoutid']);?></td>
        <td style="text-align: center"><?=htmlspecialchars($latest['bookid']);?></td>
        <td><?=htmlspecialchars($latest['title']);?></td>
        <td><?=htmlspecialchars($latest['author']);?></td>
        <td><?=htmlspecialchars($latest['publisher']);?></td>
        <td style="text-align: center"><?=htmlspecialchars($latest['rocketid']);?></td>
        <td><?=htmlspecialchars($latest['name']);?></td>
        <td><?=htmlspecialchars($latest['phone']);?></td>
        <td><?=htmlspecialchars($latest['address']);?></td>
        <td style="text-align: center"><?=GetFormattedDate($latest['checkout_date']);?></td>
        <td style="text-align: center"><?=GetFormattedDate($latest['promise_date']);?></td>
        <td style="text-align: center"><?=GetFormattedDate($latest['return_date']);?></td>
      </tr>
    </table>
    <?php endif; ?>
    <div class="error" style="font-family: sans-serif; font-size: 1.125em; margin-bottom: 0.5em">
      <?php if(!empty($returnErr)): ?>
      <?=$returnErr;?>
      <?php endif; ?>
    </div>
    <form class="radio-field" method="POST" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
      <?php foreach($_GET as $key=>$value): ?>
      <input type="hidden" name="<?=CleanInput($key);?>" value="<?=CleanInput($value);?>">
      <?php endforeach; ?>
      <table>
        <tr>
          <td class="field-label">Selected:</td>
          <td><?=GetInputSelected("checkoutid",$sel_checkoutid);?></td>
          <td><?=GetInputSelected("title",$sel_title);?></td>
          <td><?=GetInputSelected("name",$sel_name);?></td>
          <td><input class="submit-button" type="submit" value="Return Book"></td>
          <td>
            <a href="<?=ClearSelected("checkoutid");?>">
              <input class="clear-button" type="button" value="Clear">
            </a>
          </td>
          <td>
            <a style="padding-left: 0em" href="<?=ClearAll($succ,$returnErr,$latestid);?>">
              <input class="submit-button" type="button" value="Clear All">
            </a>
          </td>
        </tr>
      </table>
    </form>
    <form class="radio-field" method="GET" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
      <?php foreach($_GET as $key=>$value): ?>
      <?php if(!($key=="filtcol" || $key=="filtstr")): ?>
      <input type="hidden" name="<?=CleanInput($key);?>" value="<?=CleanInput($value);?>">
      <?php endif; ?>
      <?php endforeach; ?>
      <table style="margin-top: 0.25em">
        <tr>
          <td class="radio-label">Search Checkout:</td>
          <td>
            <input class="search" type="text" name="filtstr" value="<?=GetFilterString("filtstr");?>" size="30">
          </td>
          <td>
            <select class="search-filter" name="filtcol">
              <option <?=GetFilterColumn("","");?> value="">&nbsp;</option>
              <option <?=GetFilterColumn("filtcol","checkoutid");?> value="checkoutid">Checkout ID</option>
              <option <?=GetFilterColumn("filtcol","bookid");?> value="bookid">Book ID</option>
              <option <?=GetFilterColumn("filtcol","title");?> value="title">Book Title</option>
              <option <?=GetFilterColumn("filtcol","author");?> value="author">Book Author</option>
              <option <?=GetFilterColumn("filtcol","publisher");?> value="publisher">Book Publisher</option>
              <option <?=GetFilterColumn("filtcol","rocketid");?> value="rocketid">Rocket ID</option>
              <option <?=GetFilterColumn("filtcol","name");?> value="name">Student Name</option>
              <option <?=GetFilterColumn("filtcol","phone");?> value="phone">Student Phone</option>
              <option <?=GetFilterColumn("filtcol","address");?> value="address">Student Address</option>
              <option <?=GetFilterColumn("filtcol","checkout_date");?> value="checkout_date">Checkout Date</option>
              <option <?=GetFilterColumn("filtcol","promise_date");?> value="promise_date">Return By Date</option>
            </select>
          </td>
          <td><input class="submit-button" type="submit" value="Search"></td>
        </tr>
      </table>
    </form>  
    <table style="margin-top: 0.25em">
      <tr>
        <th><a href="<?=RepopulateUrl("sortcol","checkoutid");?>">Checkout ID</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","bookid");?>">Book ID</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","title");?>">Book Title</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","author");?>">Book Author</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","publisher");?>">Book Publisher</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","rocketid");?>">Rocket ID</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","name");?>">Student Name</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","phone");?>">Student Phone</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","address");?>">Student Address</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","checkout_date");?>">Checkout Date</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","promise_date");?>">Return By Date</a></th>
        <th class="sub-element">
          <table class="sub-table">
            <tr class="sub-row">
              <td class="button-link"><a href="<?=ClearFiltering();?>">Clear Filtering</a></td>
            </tr>
          </table>
        </th>
      </tr>
      <?php foreach($checkouts as $checkout): ?>
      <tr>
        <td style="text-align: center"><?=htmlspecialchars($checkout['checkoutid']);?></td>
        <td style="text-align: center"><?=htmlspecialchars($checkout['bookid']);?></td>
        <td><?=htmlspecialchars($checkout['title']);?></td>
        <td><?=htmlspecialchars($checkout['author']);?></td>
        <td><?=htmlspecialchars($checkout['publisher']);?></td>
        <td style="text-align: center"><?=htmlspecialchars($checkout['rocketid']);?></td>
        <td><?=htmlspecialchars($checkout['name']);?></td>
        <td><?=htmlspecialchars($checkout['phone']);?></td>
        <td><?=htmlspecialchars($checkout['address']);?></td>
        <td style="text-align: center"><?=GetFormattedDate($checkout['checkout_date']);?></td>
        <td style="text-align: center"><?=GetFormattedDate($checkout['promise_date']);?></td>
        <td class="sub-element">
          <table class="sub-table">
            <tr class="sub-row">
              <td class="button-link">
                <a href="<?=RepopulateUrl("checkoutid",htmlspecialchars($checkout['checkoutid']));?>">Select</a>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <h2>
      <span class="h2-span"><a href="../checkouts/listcheckouts.php">Back to Checkouts</a></span>
      <span class="h2-span"><a href="../books/listbooks.php">Back to Books</a></span>
      <span class="h2-span"><a href="../students/liststudents.php">Back to Students</a></span>
    </h2>
  </body>
</html>