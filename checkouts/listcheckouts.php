<?php
  //Always check authentication.
  require('../isauthenticated.php');

  //The main associative array for the query.
  $checkouts;
  //A smaller associative array of most recent checkouts per book.
  $latest;
  //All filters to be put in the mysql WHERE clause.
  $filter = "";
  //Each individual filtering criteria.
  $filters = array();
  //Number of string filter criteria.
  $filtnum = 2;
  //User inputted string filters and the columns to filter.
  for($i=0;$i<$filtnum;$i++) $filterstr[$i] = $filtercol[$i] = "";
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
        case 'return_date':
          $sortcol .= "`return_date`";
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
    for($i=0;$i<$filtnum;$i++){
      if(isset($_GET['filtercol'.$i])){
        switch($_GET['filtercol'.$i]){
          case 'checkoutid':
            $filtercol[$i] = "`checkoutid`";
            break;
          case 'bookid':
            $filtercol[$i] = "`bookid`";
            break;
          case 'title':
            $filtercol[$i] = "`title`";
            break;
          case 'author':
            $filtercol[$i] = "`author`";
            break;
          case 'publisher':
            $filtercol[$i] = "`publisher`";
            break;
          case 'rocketid':
            $filtercol[$i] = "`rocketid`";
            break;
          case 'name':
            $filtercol[$i] = "`name`";
            break;
          case 'phone':
            $filtercol[$i] = "`phone`";
            break;
          case 'address':
            $filtercol[$i] = "`address`";
            break;
          case 'checkout_date':
            $filtercol[$i] = "`checkout_date`";
            break;
          case 'promise_date':
            $filtercol[$i] = "`promise_date`";
            break;
          case 'return_date':
            $filtercol[$i] = "`return_date`";
            break;
          case '':
          default:
        }
        //Fourth, check filtering for filterstrings.
        if(!empty($filtercol[$i]) && isset($_GET['filterstr'.$i])){
          $filterstr[$i] = CleanInput($_GET['filterstr'.$i]);
          if(!empty($filterstr[$i])){
            //filterstring will need to be prepared.
            $filterstr[$i] = "%$filterstr[$i]%";
            $filters[] = $filtercol[$i]."LIKE ? ";
            $filterprepare = true;
          }
        }
      }
      //Fifth, catch empty filtercol-filterstr pairs that slip past and populate with dummy values into $filters.
      if(count($filters)<=$i){
        $filterstr[$i] = "%";
        $filtercol[$i] = "`checkoutid`";
        $filters[] = $filtercol[$i]."LIKE ? ";
        $filterprepare = true;
      }
    }
    //Sixth, check filtering for if a checkout is active or finished.
    if(isset($_GET['checkout'])){
      switch($_GET['checkout']){
        case 'active':
          $filters[] = "(`return_date`IS NULL AND`promise_date`IS NOT NULL)";
          break;
        case 'finished':
          $filters[] = "!(`return_date`IS NULL AND`promise_date`IS NOT NULL)";
          break;
        case 'both':
        default:
      }
    }
    //Seventh, populate filter variable if there's filtering.
    if(!empty($filters)){
      $filter = "WHERE".implode(" AND ",$filters);
    }
  }

  //Fetch main associative array.
  $inifile = parse_ini_file("../myproperties.ini");
  $conn = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
          or die("Connection failed:".mysqli_connect_error());
  $sql =  "SELECT";
  $sql .= "`checkoutid`,";
  $sql .= "`bookid`,`title`,`author`,`publisher`,`book_active`,";
  $sql .= "`rocketid`,`name`,`phone`,`address`,`student_active`,";
  $sql .= "`checkout_date`,`promise_date`,`return_date`";
  $sql .= "FROM`master_checkout_query`";
  $sql .= "$filter $sortcol $sortdir";
  if($filterprepare){
    $prepsql = $conn->prepare($sql);
    $prepsql->bind_param(str_repeat("s",$filtnum),...$filterstr);
    $prepsql->execute();
    $resultset = $prepsql->get_result();
    $checkouts = $resultset->fetch_all(MYSQLI_ASSOC);
  }else{
    $resultset = mysqli_query($conn,$sql);
    $checkouts = mysqli_fetch_all($resultset,MYSQLI_ASSOC);
  }
  //Fetch smaller associative array of most recent checkouts for each book.
  $sql = "SELECT`bookid`,`promise_date`,`return_date`";
  $sql .= "FROM`master_book_query`";
  $smallresultset = mysqli_query($conn,$sql);
  $latest = mysqli_fetch_all($smallresultset,MYSQLI_ASSOC);
  $conn->close();

  //Fill in NULLS.
  foreach($checkouts as &$checkout){
    //NULL return date with non-NULL promise date means checked out.
    if(is_null($checkout['return_date']) && !is_null($checkout['promise_date'])){
      $checkout['return_date'] = 'CHECKED OUT';
    }
    //Everything else that's NULL is not applicable.
    foreach($checkout as &$key){
      if(is_null($key)){
        $key = 'N/A';
      }
    }
    unset($key);
  }
  unset($checkout);

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
    $urlender = "listcheckouts.php?";
    $urlender .= http_build_query($tempGET);
    return $urlender;
  }
  //Grays out checkout selection for book-student combinations that can't do checkouts.
  function CanCheckout($checkout,$latest){
    if(!$checkout['book_active']
    ||!$checkout['student_active']
    ||GetLatestReturn($checkout['bookid'],$latest)=="CHECKED OUT"){
      echo '<td class="sub-data grayed"><a>Re-Checkout</a></td>';
    }else{
      echo '<td class="sub-data"><a href="../checkouts/checkout.php?bookid=';
      echo htmlspecialchars($checkout['bookid']);
      echo '&rocketid=';
      echo htmlspecialchars($checkout['rocketid']);
      echo '">Re-Checkout</a></td>';
    }
  }
  //Grays out return selection for books that can't be returned.
  function CanReturn($checkout){
    if(!$checkout['book_active'] || $checkout['return_date']!="CHECKED OUT"){
      echo '<td class="sub-data grayed"><a>Return Book</a></td>';
    }else{
      echo '<td class="sub-data"><a href="../checkouts/return.php?checkoutid=';
      echo htmlspecialchars($checkout['checkoutid']);
      echo '">Return Book</a></td>';
    }
  }
  //Fetches most recent return date for a book from an associative array.
  function GetLatestReturn($currentbookid,$latest){
    foreach($latest as $latestbook){
      if($currentbookid==$latestbook['bookid']){
        if(is_null($latestbook['return_date'])){
          return("CHECKED OUT");
        }else{
          return($latestbook['return_date']);
        }
      }
    }
    return "";
  }
  //Return what kind of filtering the checkout criteria is doing.
  function KindCheckout($compare){
    if(!isset($_GET['checkout']) && $compare=="both"){
      return("checked");
    }else if(isset($_GET['checkout'])){
      if($_GET['checkout']==$compare){
        return("checked");
      }else if($compare=="both"){
        return("checked");
      }
    }
    return("");
  }
  //Return the current filterstring.
  function GetFilterString($index){
    if(isset($_GET['filterstr'.$index])){
      return(htmlspecialchars($_GET['filterstr'.$index]));
    }
    return("");
  }
  //Return the current filtercolumn.
  function GetFilterColumn($value,$index){
    if(isset($_GET['filtercol'.$index])){
      if($_GET['filtercol'.$index]==$value){
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
  function BuildHistoryHRef($checkout){
    $urlender =  'listcheckouts.php?filtercol0=title&filtercol1=name';
    $urlender .= '&filterstr0='.htmlspecialchars($checkout['title']);
    $urlender .= '&filterstr1='.htmlspecialchars($checkout['name']);
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
    <title>Checkouts</title>
  </head>
  <body>
    <h1>Checkouts</h1>
    <h3>CSET Department Student Library</h3>
    <h2><a href="../index.php">Back to Home</a></h2>
    <form class="radio-field" method="GET" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
      <?php foreach($_GET as $key=>$value): ?>
      <?php if(!($key=="checkout"||preg_match("/filter/",$key))): ?>
      <input type="hidden" name="<?=CleanInput($key);?>" value="<?=CleanInput($value);?>">
      <?php endif; ?>
      <?php endforeach; ?>
      <table>
        <tr>
          <td class="radio-label">Checkout Status:</td>
          <td>
            <input type="radio" name="checkout" id="both" value="both" <?=KindCheckout("both");?>>
            <label for="both">Both</label>
          </td>
          <td>
            <input type="radio" name="checkout" id="finished" value="finished" <?=KindCheckout("finished");?>>
            <label for="finished">Finished</label>
          </td>
          <td colspan="2">
            <input type="radio" name="checkout" id="active" value="active" <?=KindCheckout("active");?>>
            <label for="active">Active</label>
          </td>
        <?php for($i=0;$i<$filtnum;$i++): ?>
        </tr>
        <tr>
          <td class="radio-label">Search Filter <?=$i+1?>:</td>
          <td colspan="2">
            <input class="search" type="text" name="filterstr<?=$i?>" value="<?=GetFilterString($i);?>">
          </td>
          <td>
            <select class="search-filter" name="filtercol<?=$i?>">
              <option <?=GetFilterColumn("",$i);?> value="">&nbsp;</option>
              <option <?=GetFilterColumn("checkoutid",$i);?> value="checkoutid">Checkout ID</option>
              <option <?=GetFilterColumn("bookid",$i);?> value="bookid">Book ID</option>
              <option <?=GetFilterColumn("title",$i);?> value="title">Book Title</option>
              <option <?=GetFilterColumn("author",$i);?> value="author">Book Author</option>
              <option <?=GetFilterColumn("publisher",$i);?> value="publisher">Book Publisher</option>
              <option <?=GetFilterColumn("rocketid",$i);?> value="rocketid">Rocket ID</option>
              <option <?=GetFilterColumn("name",$i);?> value="name">Student Name</option>
              <option <?=GetFilterColumn("phone",$i);?> value="phone">Student Phone</option>
              <option <?=GetFilterColumn("address",$i);?> value="address">Student Address</option>
              <option <?=GetFilterColumn("checkout_date",$i);?> value="checkout_date">Checkout Date</option>
              <option <?=GetFilterColumn("promise_date",$i);?> value="promise_date">Return By Date</option>
              <option <?=GetFilterColumn("return_date",$i);?> value="return_date">Date Returned</option>
            </select>
          </td>
        <?php endfor; ?>
          <td><input class="submit-button" type="submit" value="Search"></td>
          <td>
            <a href="listcheckouts.php">
              <input class="submit-button" style="margin: 0em" type="button" value="Clear">
            </a>
          </td>
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
        <th><a href="<?=RepopulateUrl("sortcol","return_date");?>">Date Returned</a></th>
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
        <td class="sub-element">
          <table class="sub-table">
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table">
                  <tr class="sub-top">
                    <td class="sub-data"><?=GetFormattedDate($checkout['checkout_date']);?></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table">
                  <tr class="sub-bottom"><?=CanCheckout($checkout,$latest);?></tr>
                </table>
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
                    <td class="sub-data"><?=GetFormattedDate($checkout['promise_date']);?></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table">
                  <tr class="sub-bottom"><?=CanReturn($checkout);?></tr>
                </table>
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
                    <td class="sub-data"><?=GetFormattedDate($checkout['return_date']);?></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table">
                  <tr class="sub-bottom">
                    <td class="sub-data">
                      <a href="<?=BuildHistoryHRef($checkout);?>">Checkout History</a>
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