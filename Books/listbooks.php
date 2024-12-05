<?php
  //Always check authentication.
  require('../isauthenticated.php');
  
  //The associative array for the query.
  $books;
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
        case 'title':
          $sortcol .= "`title`";
          break;
        case 'author':
          $sortcol .= "`author`";
          break;
        case 'publisher':
          $sortcol .= "`publisher`";
          break;
        case 'added':
          $sortcol .= "`added`";
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
        case 'bookid':
        default:
          $sortcol .= "`bookid`";
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
    //Third, check filtering for if a book is active.
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
    //Fourth, check filtering for if a book is checked out or not.
    if(isset($_GET['checkout'])){
      switch($_GET['checkout']){
        case 'checkedout':
          $filters[] = "(`return_date`IS NULL AND`promise_date`IS NOT NULL)";
          break;
        case 'notcheckedout':
          $filters[] = "!(`return_date`IS NULL AND`promise_date`IS NOT NULL)";
          break;
        case 'both':
        default:
      }
    }
    //Fifth, check filtering for filtercolumn.
    if(isset($_GET['filtercolumn'])){
      switch($_GET['filtercolumn']){
        case 'bookid':
          $filtercolumn = "`bookid`";
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
        case 'added':
          $filtercolumn = "`added`";
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
      //Sixth, check filtering for filterstring.
      if(!empty($filtercolumn) && isset($_GET['filterstring'])){
        $filterstring = CleanInput($_GET['filterstring']);
        if(!empty($filterstring) || $filterstring==0){
          //filterstring will need to be prepared.
          $filterstring = "%$filterstring%";
          $filters[] = $filtercolumn."LIKE ? ";
          $filterprepare = true;
        }
      }
    }
    //Seventh, populate filter variable if there's filtering.
    if(!empty($filters)){
      $filter = "WHERE".implode(" AND ",$filters);
    }
  }

  //Fetch associative array.
  $inifile = parse_ini_file("../myproperties.ini");
  $conn = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
          or die("Connection failed:" . mysqli_connect_error());
  $sql =  "SELECT";
  $sql .= "`bookid`,`title`,`author`,`publisher`,`added`,`active`,";
  $sql .= "`checkoutid`,";
  $sql .= "`rocketid`,`name`,`phone`,`address`,";
  $sql .= "`checkout_date`,`promise_date`,`return_date`";
  $sql .= "FROM`master_book_query`";
  $sql .= "$filter $sortcol $sortdir";
  if($filterprepare){
    $prepsql = $conn->prepare($sql);
    $prepsql->bind_param("s",$filterstring);
    $prepsql->execute();
    $resultset = $prepsql->get_result();
    $books = $resultset->fetch_all(MYSQLI_ASSOC);
  }else{
    $resultset = mysqli_query($conn,$sql);
    $books = mysqli_fetch_all($resultset,MYSQLI_ASSOC);
  }
  $conn->close();

  //Fill in NULLS.
  foreach($books as &$book){
    //NULL return date with non-NULL promise date means checked out.
    if(is_null($book['return_date']) && !is_null($book['promise_date'])){
      $book['return_date'] = 'CHECKED OUT';
    }
    //Everything else that's NULL is not applicable.
    foreach($book as &$key){
      if(is_null($key)){
        $key = 'N/A';
      }
    }
    unset($key);
  }
  unset($book);

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
    $urlender = "listbooks.php?";
    $urlender .= http_build_query($tempGET);
    return $urlender;
  }
  //Grays out checkout selection for books that can't be checked out.
  function CanCheckout($book){
    if(!$book['active'] || $book['return_date']=="CHECKED OUT"){
      echo '<td class="sub-data grayed"><a>Checkout Book</a></td>';
    }else{
      echo '<td class="sub-data"><a href="../checkouts/checkout.php?bookid=';
      echo htmlspecialchars($book['bookid']);
      echo '">Checkout Book</a></td>';
    }
  }
  //Grays out return selection for books that can't be returned.
  function CanReturn($book){
    if(!$book['active'] || $book['return_date']!="CHECKED OUT"){
      echo '<td class="sub-data grayed"><a>Return Book</a></td>';
    }else{
      echo '<td class="sub-data"><a href="../checkouts/return.php?checkoutid=';
      echo htmlspecialchars($book['checkoutid']);
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
  function BuildHistoryHRef($bookid,$title){
    $urlender =  '../checkouts/listcheckouts.php?filtercol0=bookid&filtercol1=title';
    $urlender .= '&filterstr0='.htmlspecialchars($bookid);
    $urlender .= '&filterstr1='.htmlspecialchars($title);
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
    <title>Books</title>
  </head>
  <body>
    <h1>Books</h1>
    <h3>CSET Department Student Library</h3>
    <h2><a href="../index.php">Back to Home</a></h2>
    <form class="radio-field" method="GET" action="<?=htmlspecialchars($_SERVER['PHP_SELF']);?>">
      <?php foreach($_GET as $key=>$value): ?>
      <?php if(!($key=="active"||$key=="checkout"||$key=="filtercolumn"||$key=="filterstring")): ?>
      <input type="hidden" name="<?=CleanInput($key);?>" value="<?=CleanInput($value);?>">
      <?php endif; ?>
      <?php endforeach; ?>
      <table>
        <tr>
          <td class="radio-label">Checkout Status:</td>
          <td>
            <input type="radio" name="checkout" id="c-both" value="both" <?=KindCheckout("both");?>>
            <label for="c-both">Both</label>
          </td>
          <td>
            <input type="radio" name="checkout" id="c-no" value="notcheckedout" <?=KindCheckout("notcheckedout");?>>
            <label for="c-no">Not Checked Out</label>
          </td>
          <td colspan="2">
            <input type="radio" name="checkout" id="c-yes" value="checkedout" <?=KindCheckout("checkedout");?>>
            <label for="c-yes">Checked Out</label>
          </td>
        </tr>
        <tr>
          <td class="radio-label">Circulation Status:</td>
          <td>
            <input type="radio" name="active" id="a-both" value="both" <?=KindActive("both");?>>
            <label for="a-both">Both</label>
          </td>
          <td>
            <input type="radio" name="active" id="a-yes" value="active" <?=KindActive("active");?>>
            <label for="a-yes">In Circulation</label>
          </td>
          <td colspan="2">
            <input type="radio" name="active" id="a-no" value="inactive" <?=KindActive("inactive");?>>
            <label for="a-no">Removed From Circulation</label>
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
              <option <?=GetFilterColumn("bookid");?> value="bookid">Book ID</option>
              <option <?=GetFilterColumn("title");?> value="title">Book Title</option>
              <option <?=GetFilterColumn("author");?> value="author">Book Author</option>
              <option <?=GetFilterColumn("publisher");?> value="publisher">Book Publisher</option>
              <option <?=GetFilterColumn("added");?> value="added">Book Added</option>
              <option <?=GetFilterColumn("name");?> value="name">Student Name</option>
              <option <?=GetFilterColumn("phone");?> value="phone">Student Phone</option>
              <option <?=GetFilterColumn("address");?> value="address">Student Address</option>
              <option <?=GetFilterColumn("checkout_date")?> value="checkout_date">Checkout Date</option>
              <option <?=GetFilterColumn("promise_date");?> value="promise_date">Return By Date</option>
              <option <?=GetFilterColumn("return_date");?> value="return_date">Date Returned</option>
            </select>
          </td>
          <td><input class="submit-button" type="submit" value="Search"></td>
          <td>
            <a href="listbooks.php">
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
              <td class="button-link"><a href="addbook.php">Add Book</a></td>
            </tr>
          </table>
        </th>
        <th><a href="<?=RepopulateUrl("sortcol","bookid");?>">Book ID</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","title");?>">Book Title</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","author");?>">Book Author</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","publisher");?>">Book Publisher</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","added");?>">Book Added</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","name");?>">Student Name</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","phone");?>">Student Phone</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","address");?>">Student Address</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","checkout_date");?>">Checkout Date</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","promise_date");?>">Return By Date</a></th>
        <th><a href="<?=RepopulateUrl("sortcol","return_date");?>">Date Returned</a></th>
      </tr>
      <?php foreach($books as $book): ?>
      <?php if(!$book['active']): ?>
      <tr class="grayed">
      <?php else: ?>
      <tr>
      <?php endif; ?>
        <td class="sub-element">
          <table class="sub-table">
            <tr class="sub-row">
              <td class="button-link">
                <a href="editbook.php?bookid=<?=htmlspecialchars($book['bookid']);?>">Edit Book</a>
              </td>
            </tr>
          </table>
        </td>
        <td style="text-align: center"><?=htmlspecialchars($book['bookid']);?></td>
        <td><?=htmlspecialchars($book['title']);?></td>
        <td><?=htmlspecialchars($book['author']);?></td>
        <td><?=htmlspecialchars($book['publisher']);?></td>
        <td style="text-align: center"><?=GetFormattedDate($book['added']);?></td>
        <td><?=htmlspecialchars($book['name']);?></td>
        <td><?=htmlspecialchars($book['phone']);?></td>
        <td><?=htmlspecialchars($book['address']);?></td>
        <td class="sub-element">
          <table class="sub-table">
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table">
                  <tr class="sub-top">
                    <td class="sub-data"><?=GetFormattedDate($book['checkout_date']);?></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table"><tr class="sub-bottom"><?=CanCheckout($book);?></tr></table>
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
                    <td class="sub-data"><?=GetFormattedDate($book['promise_date']);?></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table"><tr class="sub-bottom"><?=CanReturn($book);?></tr></table>
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
                    <td class="sub-data"><?=GetFormattedDate($book['return_date']);?></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr class="sub-row">
              <td class="nested-td">
                <table class="sub-table">
                  <tr class="sub-bottom">
                    <td class="sub-data">
                      <a href="<?=BuildHistoryHRef($book['bookid'],$book['title']);?>">Book History</a>
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