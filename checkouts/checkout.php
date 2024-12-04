<?php
  //Always check authentication.
  require('../isauthenticated.php');

  //Click a "Select" link,
  //That sends respective ID values to $_GET,
  //When pages reloads after the $_GET,
  //The $_GET values are placed in a hidden field in Checkout's $_POST,
  //Then $_GET is used to do a small query,
  //Which fetches values to place in the locked fields in select so the user can see them,
  //When user clicks "Checkout", only the hidden values are sent back to the webpage.
  //These values are then used to attempt a database insert.

  //The associative array for the book query.
  $books;
  //The associative array for the student query.
  $students;
  //Single row associative array showing latest checkout.
  $latest;
  //Values for the selected book from database.
  $sel_bookid = $sel_title = "";
  //Values for the selected student from database.
  $sel_rocketid = $sel_name = "";
  //The book id used for the selected book from GET or POST.
  $bookid = "";
  //The rocket id used for the selected student from GET or POST.
  $rocketid = "";
  //Columns to sort by in the book and student query.
  $b_sortcol = $s_sortcol = "";
  //Ascending or descending for column sorting.
  $b_sortdir = $s_sortdir = "";
  //User inputted columns to filter by in the book and student query.
  $b_filtcol = $s_filtcol = "";
  //User inputted strings to filter by in the book and student query.
  $b_filtstr = $s_filtstr = "";
  //Combined filters for filtcol and filtstr.
  $b_filter = $s_filter = "";
  //Is set when we need to prepare the book or student sql queries, respectively.
  $b_filtprep = $s_filtprep = false;
  //Error message for a failed checkout.
  $checkoutErr = "";
  //Set when a successful database insert has occurred and stays until a consecutive one fails.
  $succ = false;

  //FIRST, prioritize $_POST, which sends a checkout request, over $_GET, which sends filtering criteria.
  if($_SERVER['REQUEST_METHOD']=="POST"){
    //First, check that nothing in $_POST is empty.
    if(empty($_POST['bookid']) || empty($_POST['rocketid'])){
      $checkoutErr = "Incomplete selection.";
    }else{ //Second, clean and set id variables if both are not empty.
      $bookid =     CleanInput($_POST['bookid']);
      $rocketid = CleanInput($_POST['rocketid']);
    }
    //Third, check that both id variables are not empty.
    if(empty($bookid) || empty($rocketid)){
      $checkoutErr = "Incomplete selection.";
    }else{ //Fourth, validate data for our checkout request via database queries.
      //BOOK BOOK BOOK
      $inifile = parse_ini_file("../myproperties.ini");
      $conn    = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
                 or die("Connection failed:".mysqli_connect_error());
      $booksql =  "SELECT`bookid`FROM`master_book_query`";
      $booksql .= "WHERE`bookid`= ? "; //Book needs to have this id.
      $booksql .= "AND`active`IS TRUE "; //And be active.
      $booksql .= "AND!(`return_date`IS NULL AND`promise_date`IS NOT NULL)"; //And not be checked out.
      $bookstmt = $conn->prepare($booksql);
      $bookstmt->bind_param("s",$bookid);
      $bookstmt->execute();
      $bookresult = $bookstmt->get_result();
      if($bookresult->num_rows!=1){
        $checkoutErr .= "Selected Book ID is not valid for checkout. ";
      }else{ //To catch potential wildcards someone may have POSTed for bookid.
        $book = $bookresult->fetch_assoc();
        if($book['bookid']!=$bookid) $checkoutErr .= "Selected Book ID is not valid for checkout. ";
      }
      //STUDENT STUDENT STUDENT
      $studentsql =  "SELECT`rocketid`FROM`student`";
      $studentsql .= "WHERE`rocketid`= ? "; //Student needs to have this id.
      $studentsql .= "AND`active`IS TRUE"; //And be active.
      $studentstmt = $conn->prepare($studentsql);
      $studentstmt->bind_param("s",$rocketid);
      $studentstmt->execute();
      $studentresult = $studentstmt->get_result();
      if($studentresult->num_rows!=1){
        $checkoutErr .= "Selected Rocket ID is not valid for checkout.";
      }else{ //To catch potential wildcards someone may have POSTed for rocketid.
        $student = $studentresult->fetch_assoc();
        if($student['rocketid']!=$rocketid) $checkoutErr .= "Selected Rocket ID is not valid for checkout.";
      }
      $conn->close();
    }
    //Fifth, if $checkoutErr is still empty, attempt to create a checkout in the database.
    if(empty($checkoutErr)){
      $returnbydate = GetReturnDate();
      $inifile = parse_ini_file("../myproperties.ini");
      $conn    = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
                 or die("Connection failed:".mysqli_connect_error());
      $checkoutsql =  "INSERT INTO`checkout`(`bookid`,`rocketid`,`promise_date`)";
      $checkoutsql .= "VALUES( ? , ? ,'$returnbydate')";
      $checkoutstmt = $conn->prepare($checkoutsql);
      $checkoutstmt->bind_param("ss",$bookid,$rocketid);
      $succ = $checkoutstmt->execute();
      $conn->close();
      if(!$succ) $checkoutErr = "ERROR: Could not issue checkout.";
    }
    //Sixth, if checkout could not be issued, reload page with cached $_POST values so they show up in $_GET.
    if(!$succ){
      //Carry over errors and success status.
      $newGET['checkoutErr'] = $checkoutErr;
      $newGET['succ'] = $succ;
      foreach($_POST as $POSTkey=>$POSTvalue)
        $newGET[CleanInput($POSTkey)] = CleanInput($POSTvalue);
      $urlender = "checkout.php?";
      $urlender .= http_build_query($newGET);
      header("Location: $urlender");
			exit();
    }
  }
  //SECOND, check $_GET for extra values sent over prior POSTs and set variables.
  if($_SERVER['REQUEST_METHOD']=="GET"){
    if(!empty($_GET['checkoutErr'])) $checkoutErr = CleanInput($_GET['checkoutErr']);
    if(!empty($_GET['succ'])) $succ = CleanInput($_GET['succ']);
  }

  //THIRD, fetch the latest checkout if last checkout was successful.
  if($succ){
    $inifile = parse_ini_file("../myproperties.ini");
    $conn =    mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
            or die("Connection failed:".mysqli_connect_error());
    $l_sql =  "SELECT";
    $l_sql .= "`checkoutid`,";
    $l_sql .= "`bookid`,`title`,`author`,`publisher`,";
    $l_sql .= "`rocketid`,`name`,`phone`,`address`,";
    $l_sql .= "`checkout_date`,`promise_date`";
    $l_sql .= "FROM`master_checkout_query`";
    $l_sql .= "WHERE`checkoutid`=(SELECT max(checkoutid)FROM`checkout`)";
    $l_resultset = mysqli_query($conn,$l_sql);
    $latest = $l_resultset->fetch_assoc();
    $conn->close();
  }

  //FOURTH, fill selection field values when last checkout wasn't successful or if REQUEST_METHOD is GET.
  if(!$succ || $_SERVER['REQUEST_METHOD']=="GET"){
    //BOOK BOOK BOOK
    //First, check that $_GET['bookid'] isn't empty.
    if(!empty($_GET['bookid'])){
      //Second, fetch from database.
      $bookid = CleanInput($_GET['bookid']);
      $inifile = parse_ini_file("../myproperties.ini");
      $conn =    mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
                 or die("Connection failed:".mysqli_connect_error());
      $sel_b_sql =  "SELECT`bookid`,`title`";
      $sel_b_sql .= "FROM`book`";
      $sel_b_sql .= "WHERE`bookid`= ? ";
      $sel_b_prepsql = $conn->prepare($sel_b_sql);
      $sel_b_prepsql->bind_param("s",$bookid);
      $sel_b_prepsql->execute();
      $sel_b_result = $sel_b_prepsql->get_result();
      //Third, check that we get one array row.
      if($sel_b_result->num_rows==1){
        $sel_book = $sel_b_result->fetch_assoc();
        //Fourth, check matching again to catch potential wildcards someone may have sent.
        if($sel_book['bookid']==$bookid){
          $sel_bookid = htmlspecialchars($sel_book['bookid']);
          $sel_title = htmlspecialchars($sel_book['title']);
        }
      }
      $conn->close();
    }
    //STUDENT STUDENT STUDENT
    //First, check that $_GET['rocketid'] isn't empty.
    if(!empty($_GET['rocketid'])){
      //Second, fetch from database.
      $rocketid = CleanInput($_GET['rocketid']);
      $inifile = parse_ini_file("../myproperties.ini");
      $conn =    mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
                 or die("Connection failed:".mysqli_connect_error());
      $sel_s_sql =  "SELECT`rocketid`,`name`";
      $sel_s_sql .= "FROM`student`";
      $sel_s_sql .= "WHERE`rocketid`= ? ";
      $sel_s_prepsql = $conn->prepare($sel_s_sql);
      $sel_s_prepsql->bind_param("s",$rocketid);
      $sel_s_prepsql->execute();
      $sel_s_result = $sel_s_prepsql->get_result();
      //Third, check that we get one array row.
      if($sel_s_result->num_rows==1){
        $sel_student = $sel_s_result->fetch_assoc();
        //Fourth, check matching again to catch potential wildcards someone may have sent.
        if($sel_student['rocketid']==$rocketid){
          $sel_rocketid = htmlspecialchars($sel_student['rocketid']);
          $sel_name = htmlspecialchars($sel_student['name']);
        }
      }
      $conn->close();
    }
  }
  
  //FIFTH, check for book/student sort/filter when last checkout wasn't successful or if REQUEST_METHOD is GET.
  if(!$succ || $_SERVER['REQUEST_METHOD']=="GET"){
    //BOOK BOOK BOOK
    //First, populate b_sortcol variable if there's sorting.
    if(isset($_GET['b_sortcol'])){
      $b_sortcol .= "ORDER BY";
      switch($_GET['b_sortcol']){
        case 'title':
          $b_sortcol .= "`title`";
          break;
        case 'author':
          $b_sortcol .= "`author`";
          break;
        case 'publisher':
          $b_sortcol .= "`publisher`";
          break;
        case 'bookid':
        default:
          $b_sortcol .= "`bookid`";
      }
      //Second, populate b_sortdir variable if there's sorting.
      if(isset($_GET['b_sortdir'])){
        switch($_GET['b_sortdir']){
          case 'asc':
            $b_sortdir = "ASC";
            break;
          case 'desc':
          default:
            $b_sortdir = "DESC";
        }
      }
    }
    //Third, check filtering for b_filtcol.
    if(isset($_GET['b_filtcol'])){
      switch($_GET['b_filtcol']){
        case 'bookid':
          $b_filtcol .= "`bookid`";
          break;
        case 'title':
          $b_filtcol .= "`title`";
          break;
        case 'author':
          $b_filtcol .= "`author`";
          break;
        case 'publisher':
          $b_filtcol .= "`publisher`";
          break;
        case '':
        default:
      }
      //Fourth, check filtering for b_filtstr.
      if(!empty($b_filtcol) && isset($_GET['b_filtstr'])){
        $b_filtstr = CleanInput($_GET['b_filtstr']);
        if(!empty($b_filtstr)){
          //b_filtstr will need to be prepared.
          $b_filtstr = "%$b_filtstr%";
          $b_filter = "AND".$b_filtcol."LIKE ? ";
          $b_filtprep = true;
        }
      }
    }
    //STUDENT STUDENT STUDENT
    //First, populate s_sortcol variable if there's sorting.
    if(isset($_GET['s_sortcol'])){
      $s_sortcol .= "ORDER BY";
      switch($_GET['s_sortcol']){
        case 'name':
          $s_sortcol .= "`name`";
          break;
        case 'phone':
          $s_sortcol .= "`phone`";
          break;
        case 'address':
          $s_sortcol .= "`address`";
          break;
        case 'rocketid':
        default:
          $s_sortcol .= "`rocketid`";
      }
      //Second, populate s_sortdir variable if there's sorting.
      if(isset($_GET['s_sortdir'])){
        switch($_GET['s_sortdir']){
          case 'asc':
            $s_sortdir = "ASC";
            break;
          case 'desc':
          default:
            $s_sortdir = "DESC";
        }
      }
    }
    //Third, check filtering for s_filtcol.
    if(isset($_GET['s_filtcol'])){
      switch($_GET['s_filtcol']){
        case 'rocketid':
          $s_filtcol = "`rocketid`";
          break;
        case 'name':
          $s_filtcol = "`name`";
          break;
        case 'phone':
          $s_filtcol = "`phone`";
          break;
        case 'address':
          $s_filtcol = "`address`";
          break;
        case '':
        default:
      }
      //Fourth, check filtering for s_filtstr.
      if(!empty($s_filtcol) && isset($_GET['s_filtstr'])){
        $s_filtstr = CleanInput($_GET['s_filtstr']);
        if(!empty($s_filtstr)){
          //s_filtstr will need to be prepared.
          $s_filtstr = "%$s_filtstr%";
          $s_filter = "AND".$s_filtcol."LIKE ? ";
          $s_filtprep = true;
        }
      }
    }
  }

  //SIXTH, fetch the student and book associative arrays regardless of success or REQUEST_METHOD.
  if(true){
    $inifile = parse_ini_file("../myproperties.ini");
    $conn = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
            or die("Connection failed:".mysqli_connect_error());
    //BOOK BOOK BOOK
    //Only grab books that are active and not checked out. Requires the master_book_query.
    $b_sql =  "SELECT`bookid`,`title`,`author`,`publisher`";
    $b_sql .= "FROM`master_book_query`";
    $b_sql .= "WHERE`active`IS TRUE ";
    $b_sql .= "AND !(`return_date`IS NULL AND`promise_date`IS NOT NULL)";
    $b_sql .= "$b_filter $b_sortcol $b_sortdir";
    if($b_filtprep){
      $b_prepsql = $conn->prepare($b_sql);
      $b_prepsql->bind_param("s",$b_filtstr);
      $b_prepsql->execute();
      $b_resultset = $b_prepsql->get_result();
      $books = $b_resultset->fetch_all(MYSQLI_ASSOC);
    }else{
      $b_resultset = mysqli_query($conn,$b_sql);
      $books = mysqli_fetch_all($b_resultset,MYSQLI_ASSOC);
    }
    //STUDENT STUDENT STUDENT 
    //Only grab students that are active.
    $s_sql =  "SELECT`rocketid`,`name`,`phone`,`address`";
    $s_sql .= "FROM`student`";
    $s_sql .= "WHERE`active`IS TRUE ";
    $s_sql .= "$s_filter $s_sortcol $s_sortdir";
    if($s_filtprep){
      $s_prepsql = $conn->prepare($s_sql);
      $s_prepsql->bind_param("s",$s_filtstr);
      $s_prepsql->execute();
      $s_resultset = $s_prepsql->get_result();
      $students = $s_resultset->fetch_all(MYSQLI_ASSOC);
    }else{
      $s_resultset = mysqli_query($conn,$s_sql);
      $students = mysqli_fetch_all($s_resultset,MYSQLI_ASSOC);
    }
    $conn->close();
  }

  //FUNCTIONS
  //Returns a modified version of GET for URL purposes.
  function ModifiedGET($key,$value){
    $tempGET = array_merge([], $_GET);
    //Check for same keys first and modify/add as necessary.
    foreach($_GET as $GETkey=>$GETvalue){
      //Catch that we're trying to sort on the same book column as before.
      //BOOK BOOK BOOK
      if(($GETkey==$key) && ($key=="b_sortcol") && ($GETvalue==$value)){
        //If sort direction isn't set, then it's defaulted to ascending, so set to descending.
        if(!isset($tempGET['b_sortdir'])){
          $tempGET['b_sortdir'] = "desc";
        }else{ //If it is set, then we're swapping between the two.
          switch($tempGET['b_sortdir']){
            case 'asc':
              $tempGET['b_sortdir'] = "desc";
              break;
            case 'desc':
            default:
              $tempGET['b_sortdir'] = "asc";
          }
        }
      }
      //STUDENT STUDENT STUDENT
      else if(($GETkey==$key) && ($key=="s_sortcol") && ($GETvalue==$value)){
        //If sort direction isn't set, then it's defaulted to ascending, so set to descending.
        if(!isset($tempGET['s_sortdir'])){
          $tempGET['s_sortdir'] = "desc";
        }else{ //If it is set, then we're swapping between the two.
          switch($tempGET['s_sortdir']){
            case 'asc':
              $tempGET['s_sortdir'] = "desc";
              break;
            case 'desc':
            default:
              $tempGET['s_sortdir'] = "asc";
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
    $urlender = "checkout.php?";
    $urlender .= http_build_query($tempGET);
    return $urlender;
  }
  //Gets rid of the current book selection.
  function ClearSelected($key){
    $tempGET = array_merge([], $_GET);
    foreach($_GET as $GETkey=>$GETvalue){
      if($GETkey==$key) unset($tempGET[$key]);
    }
    $urlender = "checkout.php?";
    $urlender .= http_build_query($tempGET);
    return($urlender);
  }
  //To remove book filtering.
  function ClearBookFiltering(){
    $tempGET = array_merge([], $_GET);
    foreach($_GET as $key=>$value){
      if($key=="b_filtcol" || $key=="b_filtstr") unset($tempGET[$key]);
    }
    $urlender = "checkout.php?";
    $urlender .= http_build_query($tempGET);
    return($urlender);
  }
  //To remove student filtering.
  function ClearStudentFiltering(){
    $tempGET = array_merge([], $_GET);
    foreach($_GET as $key=>$value){
      if($key=="s_filtcol" || $key=="s_filtstr") unset($tempGET[$key]);
    }
    $urlender = "checkout.php?";
    $urlender .= http_build_query($tempGET);
    return($urlender);
  }
  //Echoes values in an html input for a selected book while checking for array existence.
  function GetInputSelectedBook($key,$value){
    if($key=="bookid"){
      echo '<input class="textbox" style="font-weight: bold" type="text" name="bookid" value="Book ID: ';
      echo $value.'" size="20" disabled/>';
    }else if($key=="title"){
      echo '<input class="textbox" style="font-weight: bold" type="text" name="title" value="Book Title: ';
      echo $value.'" size="50" disabled/>';
    }
  }
  //Echoes values in an html input for a selected student while checking for array existence.
  function GetInputSelectedStudent($key,$value){
    if($key=="rocketid"){
      echo '<input class="textbox" type="text" style="font-weight: bold" name="rocketid" value="Rocket ID: ';
      echo $value.'" size="20" disabled/>';
    }else if($key=="name"){
      echo '<input class="textbox" type="text" style="font-weight: bold" name="name" value="Student Name: ';
      echo $value.'" size="50" disabled/>';
    }
  }
  //Return the current filterstring.
  function GetFilterString($key){
    if(isset($_GET[$key]))
      return(CleanInput($_GET[$key]));
    else
      return("");
  }
  //Return the current filtercolumn.
  function GetFilterColumn($key,$value){
    if(isset($_GET[$key])){
      if($_GET[$key]==$value)
        return('selected="selected"');
    }
    return("");
  }
  //Takes today's date and returns the date 30 days from now.
  function GetReturnDate(){
    date_default_timezone_set('America/Detroit');
    $date = date('Y-m-d');
    $date = date_create($date);
    date_add($date,date_interval_create_from_date_string("30 days"));
    return(date_format($date,"Y-m-d"));
  }
  //Returns strings as date formats all in the same format.
  function GetFormattedDate($string){
    if(!empty($string)){
      $date = strtotime($string);
      if($date!==false) return(date('Y-M-d',$date));
      else return(htmlspecialchars($string));
    }
    return("No Date Found");
  }
  //Distrust all user input.
  function CleanInput($data){
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return($data);
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php require('../inc-stdmeta.php'); ?>
    <title>Checkout Book</title>
  </head>
  <body>
    <h1>Checkout Book</h1>
    <h3>CSET Department Student Library</h3>
    <h2>
      <span class="h2-span"><a href="../checkouts/listcheckouts.php">Back to Checkouts</a></span>
      <span class="h2-span"><a href="../books/listbooks.php">Back to Books</a></span>
      <span class="h2-span"><a href="../students/liststudents.php">Back to Students</a></span>
    </h2>
    <?php if($succ): ?>
    <h3 style="margin-bottom: 0.25em">Successfully Checked Out Book:</h3>
    <table>
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
        <td style="test-align: center"><?=GetFormattedDate($latest['checkout_date']);?></td>
        <td class="text-align: center"><?=GetFormattedDate($latest['promise_date']);?></td>
      </tr>
    </table>
    <?php endif; ?>
    <span class="error">
      <?php if(!empty($checkoutErr)): ?>
      <?=$checkoutErr;?>
      <?php endif; ?>
    </span>
    <form class="radio-field" method="POST" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
      <?php foreach($_GET as $key=>$value): ?>
      <input type="hidden" name="<?=CleanInput($key);?>" value="<?=CleanInput($value);?>">
      <?php endforeach; ?>
      <?php if($succ): ?>
      <input type="hidden" name="succ" value="<?=CleanInput($succ);?>">
      <?php endif; ?>
      <table>
        <tr>
          <td class="field-label">Selected Book:</td>
          <td><?=GetInputSelectedBook("bookid",$sel_bookid);?></td>
          <td><?=GetInputSelectedBook("title",$sel_title);?></td>
          <td>
            <a href="<?=ClearSelected("bookid");?>">
              <input class="clear-button" type="button" value="Clear">
            </a>
          </td>
        </tr>
        <tr>
          <td class="field-label">Selected Student:</td>
          <td><?=GetInputSelectedStudent("rocketid",$sel_rocketid);?></td>
          <td><?=GetInputSelectedStudent("name",$sel_name);?></td>
          <td>
            <a href="<?=ClearSelected("rocketid");?>">
              <input class="clear-button" type="button" value="Clear">
            </a>
          </td>
          <td>
            <a href="checkout.php">
              <input class="clear-button" type="button" value="Clear All">
            </a>
          </td>
          <td><input class="submit-button" type="submit" value="Checkout"></td>
        </tr>
      </table>
    </form>
    <div class="displaypair">
      <form class="radio-field" method="GET" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
        <?php foreach($_GET as $key=>$value): ?>
        <?php if(!($key=="b_filtcol"||$key=="b_filtstr")): ?>
        <input type="hidden" name="<?=CleanInput($key);?>" value="<?=CleanInput($value);?>">
        <?php endif; ?>
        <?php endforeach; ?>
        <table style="margin-top: 0.25em; width: 100%">
          <tr>
            <td class="radio-label">Search Book:</td>
            <td>
              <input class="search" type="text" name="b_filtstr" value="<?=GetFilterString("b_filtstr");?>" size="30">
            </td>
            <td>
              <select class="search-filter" name="b_filtcol">
                <option <?=GetFilterColumn("","");?> value="">&nbsp;</option>
                <option <?=GetFilterColumn("b_filtcol","bookid");?> value="bookid">Book ID</option>
                <option <?=GetFilterColumn("b_filtcol","title");?> value="title">Book Title</option>
                <option <?=GetFilterColumn("b_filtcol","author");?> value="author">Book Author</option>
                <option <?=GetFilterColumn("b_filtcol","publisher");?> value="publisher">Book Publisher</option>
              </select>
            </td>
            <td><input class="submit-button" type="submit" value="Search"></td>
          </tr>
        </table>
      </form>  
      <table style="margin-top: 0.25em; width: 100%">
        <tr>
          <th><a href="<?=ClearBookFiltering();?>">Clear Filtering</a></th>
          <th><a href="<?=RepopulateUrl("b_sortcol","bookid");?>">Book ID</a></th>
          <th><a href="<?=RepopulateUrl("b_sortcol","title");?>">Book Title</a></th>
          <th><a href="<?=RepopulateUrl("b_sortcol","author");?>">Book Author</a></th>
          <th><a href="<?=RepopulateUrl("b_sortcol","publisher");?>">Book Publisher</a></th>
        </tr>
        <?php foreach($books as $book): ?>
        <tr>
          <td class="sub-element">
            <table class="sub-table">
              <tr class="sub-row">
                <td class="button-link">
                  <a href="<?=RepopulateUrl("bookid",htmlspecialchars($book['bookid']));?>">Select</a>
                </td>
              </tr>
            </table>
          </td>
          <td><?=htmlspecialchars($book['bookid']);?></td>
          <td><?=htmlspecialchars($book['title']);?></td>
          <td><?=htmlspecialchars($book['author']);?></td>
          <td><?=htmlspecialchars($book['publisher']);?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <div class="displaypair">
      <form class="radio-field" method="GET" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
        <?php foreach($_GET as $key=>$value): ?>
        <?php if(!($key=="s_filtcol"||$key=="s_filtstr")): ?>
        <input type="hidden" name="<?=CleanInput($key);?>" value="<?=CleanInput($value);?>">
        <?php endif; ?>
        <?php endforeach; ?>
        <table style="margin-top: 0.25em; width: 100%">
          <tr>
            <td class="radio-label">Search Student:</td>
            <td>
              <input class="search" type="text" name="s_filtstr" value="<?=GetFilterString("s_filtstr");?>" size="30">
            </td>
            <td>
              <select class="search-filter" name="s_filtcol">
                <option <?=GetFilterColumn("","");?> value="">&nbsp;</option>
                <option <?=GetFilterColumn("s_filtcol","rocketid");?> value="rocketid">Rocket ID</option>
                <option <?=GetFilterColumn("s_filtcol","name");?> value="name">Student Name</option>
                <option <?=GetFilterColumn("s_filtcol","phone");?> value="phone">Student Phone</option>
                <option <?=GetFilterColumn("s_filtcol","address");?> value="address">Student Address</option>
              </select>
            </td>
            <td><input class="submit-button" type="submit" value="Search"></td>
          </tr>
        </table>
      </form>  
      <table style="margin-top: 0.25em; width: 100%">
        <tr>
          <th><a href="<?=ClearStudentFiltering();?>">Clear Filtering</a></th>
          <th><a href="<?=RepopulateUrl("s_sortcol","rocketid");?>">Rocket ID</a></th>
          <th><a href="<?=RepopulateUrl("s_sortcol","name");?>">Student Name</a></th>
          <th><a href="<?=RepopulateUrl("s_sortcol","phone");?>">Student Phone</a></th>
          <th><a href="<?=RepopulateUrl("s_sortcol","address");?>">Student Address</a></th>
        </tr>
        <?php foreach($students as $student): ?>
        <tr>
          <td class="sub-element">
            <table class="sub-table">
              <tr class="sub-row">
                <td class="button-link">
                  <a href="<?=RepopulateUrl("rocketid",htmlspecialchars($student['rocketid']));?>">Select</a>
                </td>
              </tr>
            </table>
          </td>
          <td><?=htmlspecialchars($student['rocketid']);?></td>
          <td><?=htmlspecialchars($student['name']);?></td>
          <td><?=htmlspecialchars($student['phone']);?></td>
          <td><?=htmlspecialchars($student['address']);?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <h2>
      <span class="h2-span"><a href="../checkouts/listcheckouts.php">Back to Checkouts</a></span>
      <span class="h2-span"><a href="../books/listbooks.php">Back to Books</a></span>
      <span class="h2-span"><a href="../students/liststudents.php">Back to Students</a></span>
    </h2>
  </body>
</html>