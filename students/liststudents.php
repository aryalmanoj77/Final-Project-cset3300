<?php
  //Always check authentication.
  require('../isauthenticated.php');

  //The associative array for the query.
  $students;
  //All filters to be put in the mysql WHERE clause.
  $filter = "";
  //Each individual filtering criteria.
  $filters = array();
  //User inputted filter string.
  $filterstring = "";
  //The column to filter inputted filter string on.
  $filtercolumn = "";
  //If we need to prepare the sql query or not.
  $filterprepare = false;
  //Column to order by.
  $sortcol = "";
  //Ascending or descending for sortcol.
  $sortdir = "";

  //Check for sorting and filtering criteria.
  if($_SERVER['REQUEST_METHOD']=="GET"){
    //First, populate sortcol variable if there's sorting.
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
        case 'added':
          $sortcol .= "`added`";
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
        case 'checkout_date':
          $sortcol .= "`checkout_date`";
          break;
        case 'promise_date':
          $sortcol .= "`promise_date`";
          break;
        case 'return_date':
          $sortcol .= "`return_date`";
          break;
        case 'rocketid':
        default:
          $sortcol .= "`rocketid`";
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
    //Third, check filtering for if a student is active.
    if(isset($_GET['active'])){
      switch($_GET['active']){
        case 'active':
          $filters[] = "`active`IS TRUE";
          break;
        case 'inactive':
          $filters[] = "`active`IS FALSE";
          break;
        case 'both':
        default:
      }
    }
    //Fourth, check filtering for filtercolumn.
    if(isset($_GET['filtercolumn'])){
      switch($_GET['filtercolumn']){
        case 'rocketid':
          $filtercolumn = "`rocketid`";
          break;
        case 'name':
          $filtercolumn = "`name`";
          break;
        case 'phone':
          $filtercolumn = "`phone`";
          break;
        case 'address':
          $filtercolumn = "`address`";
          break;
        case 'added':
          $filtercolumn = "`added`";
          break;
        case 'title':
          $filtercolumn = "`title`";
          break;
        case 'author':
          $filtercolumn = "`author`";
          break;
        case 'publisher':
          $filtercolumn = "`publisher`";
          break;
        case 'checkout_date':
          $filtercolumn = "`checkout_date`";
          break;
        case 'promise_date':
          $filtercolumn = "`promise_date`";
          break;
        case 'return_date':
          $filtercolumn = "`return_date`";
          break;
        case '':
        default:
      }
      //Fifth, check filtering for filterstring.
      if(!empty($filtercolumn) && isset($_GET['filterstring'])){
        $filterstring = CleanInput($_GET['filterstring']);
        if(!empty($filterstring)){
          //filterstring will need to be prepared.
          $filterstring = "%$filterstring%";
          $filters[] = $filtercolumn."LIKE ? ";
          $filterprepare = true;
        }
      }
    }
    //Sixth, populate filter variable if there's filtering.
    if(!empty($filters)){
      $filter = "WHERE".implode(" AND ",$filters);
    }
  }

  //Fetch associative array.
  $inifile = parse_ini_file("../myproperties.ini");
  $conn = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
          or die("Connection failed:".mysqli_connect_error());
  $sql =  "SELECT";
  $sql .= "`rocketid`,`name`,`phone`,`address`,`added`,`active`,";
  $sql .= "`checkoutid`,";
  $sql .= "`bookid`,`title`,`author`,`publisher`,";
  $sql .= "`checkout_date`,`promise_date`,`return_date`";
  $sql .= "FROM`master_student_query`";
  $sql .= "$filter $sortcol $sortdir";
  if($filterprepare){
    $prepsql = $conn->prepare($sql);
    $prepsql->bind_param("s",$filterstring);
    $prepsql->execute();
    $resultset = $prepsql->get_result();
    $students = $resultset->fetch_all(MYSQLI_ASSOC);
  }else{
    $resultset = mysqli_query($conn,$sql);
    $students = mysqli_fetch_all($resultset,MYSQLI_ASSOC);
  }
  $conn->close();

  //Fill in NULLS.
  foreach($students as &$student){
    //NULL return date with non-NULL promise date means checked out.
    if(is_null($student['return_date']) && !is_null($student['promise_date'])){
      $student['return_date'] = 'CHECKED OUT';
    }
    //Everything else that's NULL is not applicable.
    foreach($student as &$key){
      if(is_null($key)){
        $key = 'N/A';
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
  //Grays out checkout selection for students who can't check out books.
  function CanCheckout($student){
    if(!$student['active']){
      echo '<td class="sub-data grayed"><a>Checkout Student</a></td>';
    }else{
      echo '<td class="sub-data"><a href="../checkouts/checkout.php?rocketid=';
      echo htmlspecialchars($student['rocketid']);
      echo '">Checkout Student</a></td>';
    }
  }
  //Grays out return selection for books that can't be returned.
  function CanReturn($student){
    if($student['return_date']!="CHECKED OUT"){
      echo '<td class="sub-data grayed"><a>Return Book</a></td>';
    }else{
      echo '<td class="sub-data"><a href="../checkouts/return.php?checkoutid=';
      echo htmlspecialchars($student['checkoutid']);
      echo '">Return Book</a></td>';
    }
  }
  //Return what kind of filtering the active criteria is doing.
  function KindActive($compare){
    if(!isset($_GET['active']) && $compare=="both"){
      return("checked");
    }else if(isset($_GET['active'])){
      if($_GET['active']==$compare){
        return("checked");
      }else if($compare=="both"){
        return("checked");
      }
    }
    return("");
  }
  //Return the current filterstring.
  function GetFilterString(){
    if(isset($_GET['filterstring'])){
      return(htmlspecialchars($_GET['filterstring']));
    }
    return("");
  }
  //Return the current filtercolumn.
  function GetFilterColumn($value){
    if(isset($_GET['filtercolumn'])){
      if($_GET['filtercolumn']==$value){
        return('selected="selected"');
      }
    }
    return("");
  }
  //Returns strings as date formats all in the same format.
  function GetFormattedDate($string){
    if(!empty($string)){
      $date = strtotime($string);
      if($date!==false){
        return date('Y-m-d',$date);
      }else{
        return htmlspecialchars($string);
      }
    }
    return "No Date Found";
  }
  //Because the history anchor header reference is absurdly long.
  function BuildHistoryHRef($rocketid,$name){
    $urlender =  '../checkouts/listcheckouts.php?filtercol0=rocketid&filtercol1=name';
    $urlender .= '&filterstr0='.htmlspecialchars($rocketid);
    $urlender .= '&filterstr1='.htmlspecialchars($name);
    return $urlender;
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
    <title>Students</title>
  </head>
  <body>
    <h1>Students</h1>
    <h3>CSET Department Student Library</h3>
    <h2><a href="../index.php">Back to Home</a></h2>
    <form class="radio-field" method="GET" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
      <?php foreach($_GET as $key=>$value): ?>
      <?php if(!($key=="active"||$key=="filtercolumn"||$key=="filterstring")): ?>
      <input type="hidden" name="<?=CleanInput($key);?>" value="<?=CleanInput($value);?>">
      <?php endif; ?>
      <?php endforeach; ?>
      <table>
        <tr>
          <td class="radio-label">Activity Status:</td>
          <td>
            <input type="radio" name="active" id="active-both" value="both" <?=KindActive("both");?>>
            <label for="active-both">Both</label>
          </td>
          <td>
            <input type="radio" name="active" id="active-active" value="active" <?=KindActive("active");?>>
            <label for="active-active">Active</label>
          </td>
          <td colspan="2">
            <input type="radio" name="active" id="active-inactive" value="inactive" <?=KindActive("inactive");?>>
            <label for="active-inactive">Inactive</label>
          </td>
        </tr>        
        <tr>
          <td class="radio-label">Search Filter:</td>
          <td colspan="2">
            <input class="search" type="text" name="filterstring" value="<?=GetFilterString();?>">
          </td>
          <td>
            <select class="search-filter" name="filtercolumn">
              <option <?=GetFilterColumn("");?> value="">&nbsp;</option>
              <option <?=GetFilterColumn("rocketid");?> value="rocketid">Rocket ID</option>
              <option <?=GetFilterColumn("name");?> value="name">Student Name</option>
              <option <?=GetFilterColumn("phone");?> value="phone">Student Phone</option>
              <option <?=GetFilterColumn("address");?> value="address">Student Address</option>
              <option <?=GetFilterColumn("added")?> value="added">Student Added</option>
              <option <?=GetFilterColumn("title");?> value="title">Book Title</option>
              <option <?=GetFilterColumn("author");?> value="author">Book Author</option>
              <option <?=GetFilterColumn("publisher");?> value="publisher">Book Publisher</option>
              <option <?=GetFilterColumn("checkout_date")?> value="checkout_date">Checkout Date</option>
              <option <?=GetFilterColumn("promise_date");?> value="promise_date">Return By Date</option>
              <option <?=GetFilterColumn("return_date");?> value="return_date">Date Returned</option>
            </select>
          </td>
          <td><input class="submit-button" type="submit" value="Search"></td>
          <td>
            <a href="liststudents.php">
              <input class="submit-button" style="margin: 0em" type="button" value="Clear">
            </a>
          </td>
        </tr>
      </table>
    </form>
    <table style="margin-top: 0.25em">
      <tr>
        <th class="sub-element">
          <table class="sub-table">
            <tr class="sub-row">
              <td class="button-link"><a href="addstudent.php">Add Student</a></td>
            </tr>
          </table>
        </th>
        <th><a href="<?=RepopulateUrl("sortcol","rocketid");?>">Rocket ID</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","name");?>">Student Name</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","phone");?>">Student Phone</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","address");?>">Student Address</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","added");?>">Student Added</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","title");?>">Book Title</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","author");?>">Book Author</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","publisher");?>">Book Publisher</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","checkout_date");?>">Checkout Date</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","promise_date");?>">Return By Date</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","return_date");?>">Date Returned</a></th>
      </tr>
      <?php foreach($students as $student): ?>
      <?php if(!$student['active']): ?>
      <tr class="grayed">
      <?php else: ?>
      <tr>
      <?php endif; ?>
        <td class="sub-element">
          <table class="sub-table">
            <tr class="sub-row">
              <td class="button-link">
                <a href="editstudent.php?rocketid=<?=htmlspecialchars($student['rocketid']);?>">
                  Edit Student
                </a>
              </td>
            </tr>
          </table>
        </td>
        <td style="text-align: center"><?=htmlspecialchars($student['rocketid']);?></td>
        <td><?=htmlspecialchars($student['name']);?></td>
        <td><?=htmlspecialchars($student['phone']);?></td>
        <td><?=htmlspecialchars($student['address']);?></td>
        <td style="text-align: center"><?=GetFormattedDate($student['added']);?></td>
        <td><?=htmlspecialchars($student['title']);?></td>
        <td><?=htmlspecialchars($student['author']);?></td>
        <td><?=htmlspecialchars($student['publisher']);?></td>
        <td class="sub-element">
          <table class="sub-table">
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table">
                  <tr class="sub-top">
                    <td class="sub-data"><?=GetFormattedDate($student['checkout_date']);?></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table"><tr class="sub-bottom"><?=CanCheckout($student);?></tr></table>
              </td>
            </tr>
          </table>
        </td>
        <td class="sub-element">
          <table class="sub-table">
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table">
                  <tr class="sub-top">
                    <td class="sub-data"><?=GetFormattedDate($student['promise_date']);?></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table"><tr class="sub-bottom"><?=CanReturn($student);?></tr></table>
              </td>
            </tr>
          </table>
        </td>
        <td class="sub-element">
          <table class="sub-table">
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table">
                  <tr class="sub-top">
                    <td class="sub-data"><?=GetFormattedDate($student['return_date']);?></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table">
                  <tr class="sub-bottom">
                    <td class="sub-data">
                      <a href="<?=BuildHistoryHRef($student['rocketid'],$student['name']);?>">Student History</a>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <h2><a href="../index.php">Back to Home</a></h2>
  </body>
</html>