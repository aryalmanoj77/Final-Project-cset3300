<?php
  session_start();
  //All filters in the mysql WHERE clause.
  $filter = "";
  //Each individual filtering criteria.
  $filters = array();
  //Column to order by.
  $sortcol = "";
  //Ascending or descending for sortcol.
  $sortdir = "";

  //Check for sorting and filtering criteria.
  if($_SERVER['REQUEST_METHOD']=="GET"){
    //First, populate sortcol variable is there's sorting.
    if(isset($_GET['sortcol'])){
      $sortcol .= "ORDER BY";
      switch($_GET['sortcol']){
        case 'author':
          $sortcol .= "`author`";
          break;
        case 'publisher':
          $sortcol .= "`publisher`";
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
        case 'promise_date':
          $sortcol .= "`promise_date`";
          break;
        case 'return_date':
          $sortcol .= "`return_date`";
          break;
        case 'title':
        default:
          $sortcol .= "`title`";
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
    //Fourth, checking filtering for if a book is checked out or not.
    if(isset($_GET['checkout'])){
      switch($_GET['checkout']){
        case 'checkedout':
          $filters[] = "(`return_date`IS NULL&&`promise_date`IS NOT NULL)";
          break;
        case 'notcheckedout':
          $filters[] = "!(`return_date`IS NULL&&`promise_date`IS NOT NULL)";
          break;
        case 'both':
        default:
      }
    }
    //Fifth, populate filter variable if there's filtering.
    if(!empty($filters)){
      $filter = "WHERE".implode(" AND ",$filters);
    }
  }

  //Fetch associative array.
  $inifile = parse_ini_file("../myproperties.ini");
  $conn = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
          or die("Connection failed:" . mysqli_connect_error());
	//$sql = "SELECT `name`, `email`, `phone`, `bgroup`, `id` FROM `users` $sortcol $sortdir";
  $sql = "SELECT`bookid`,`title`,`author`,`publisher`,`active`,`checkoutid`,`rocketid`,`name`,`phone`,`address`,`promise_date`,`return_date`";
  $sql .= "FROM`master_book_query`";
  $sql .= "$filter $sortcol $sortdir";
  $resultset = mysqli_query($conn,$sql);
  $books = mysqli_fetch_all($resultset,MYSQLI_ASSOC);

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
    $urlender = "listbooks.php?";
    $urlender .= http_build_query($tempGET);
    return $urlender;
  }
  //Grays out checkout selection for books that can't be checked out.
  function CanCheckout($book){
    if(!$book['active']){
      echo '<td><a>Check Out</a></td>';
    }
    else if($book['return_date']=="CHECKED OUT"){
      echo '<td class="grayed"><a>Check Out</a></td>';
    }
    else{
      echo '<td><a href="checkoutbook.php?bookid='.$book['bookid'].'">Check Out</a></td>';
    }
  }
  //Grays out return selection for books that can't be returned.
  function CanReturn($book){
    if(!$book['active']){
      echo '<td><a>Return</a></td>';
    }
    else if($book['return_date']!="CHECKED OUT"){
      echo '<td class="grayed"><a>Return</a></td>';
    }
    else{
      echo '<td><a href="returnbook.php?bookid='.$book['bookid'].'">Return</a></td>';
    }
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
  //Return what kind of filtering the checkout criteria is doing.
  function KindCheckout(){
    if((isset($_GET['checkout']))){
      switch($_GET['checkout']){
        case 'notcheckedout':
          return("notcheckedout");
        case 'checkedout':
          return("checkedout");
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
    <title>Books</title>
  </head>
  <body>
    <h1>Books</h1>
    <form class="radio-field" method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <?php foreach($_GET as $key=>$value): ?>
    <?php if(!($key=="active"||$key=="checkout")): ?>
      <input type="hidden" name="<?= $key; ?>" value="<?= $value; ?>">
    <?php endif; ?>
    <?php endforeach; ?>
      <table>
        <tr>
          <td>Checkout Status:</td>
          <td><input type="radio" name="checkout" value="both" <?php if(KindCheckout()=="both") echo "checked" ?>>Both</td>
          <td><input type="radio" name="checkout" value="notcheckedout" <?php if(KindCheckout()=="notcheckedout") echo "checked" ?>>Not Checked Out</td>
          <td><input type="radio" name="checkout" value="checkedout" <?php if(KindCheckout()=="checkedout") echo "checked" ?>>Checked Out</td>
        </tr>
        <tr>
          <td>Circulation Status:</td>
          <td><input type="radio" name="active" value="both" <?php if(KindActive()=="both") echo "checked" ?>>Both</td>
          <td><input type="radio" name="active" value="active" <?php if(KindActive()=="active") echo "checked" ?>>In Circulation</td>
          <td><input type="radio" name="active" value="inactive" <?php if(KindActive()=="inactive") echo "checked" ?>>Removed From Circulation</td>
        </tr>
        <tr><td><input class="submit-button" type="submit" value="Submit"></td></tr>
      </table>
      <br/>
    </form>
    <table>
      <tr>
        <th><a href="addbook.php">Add Book</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","title"); ?>">Title</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","author"); ?>">Author</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","publisher"); ?>">Publisher</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","name"); ?>">Name</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","phone"); ?>">Phone</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","address"); ?>">Address</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","promise_date"); ?>">Promise Date</a></th>
        <th><a href="<?= RepopulateUrl("sortcol","return_date"); ?>">Return Date</a></th>
        <th><a>Check Out</a></th>
        <th><a>Return</a></th>
        <th><a>History</a></th>
      </tr>
    <?php foreach($books as $book): ?>
    <?php if(!$book['active']): ?>
      <tr class="grayed">
    <?php else: ?>
      <tr>
    <?php endif; ?>
        <td><a href="editbook.php?bookid=<?= $book['bookid']; ?>">Edit Book</a></td>
        <td><?= htmlspecialchars($book['title']); ?></td>
        <td><?= htmlspecialchars($book['author']); ?></td>
        <td><?= htmlspecialchars($book['publisher']); ?></td>
        <td><?= htmlspecialchars($book['name']); ?></td>
        <td><?= htmlspecialchars($book['phone']); ?></td>
        <td><?= htmlspecialchars($book['address']); ?></td>
        <td><?= htmlspecialchars($book['promise_date']); ?></td>
        <td><?= htmlspecialchars($book['return_date']); ?></td>
      <?php CanCheckout($book); ?>
      <?php  CanReturn($book); ?>
        <td><a href="listbookhistory.php?bookid=<?= $book['bookid']; ?>">History</a></td>
      </tr>
    <?php endforeach; ?>
    </table>
    <h3><a href="../index.php">Back to Index</a></h3>
    <h3><a href="addbook.php">Add a Book</a></h3>
    <h3><a href="editbook.php">Edit Book</a></h3>
    <h3><a href="checkoutbook.php">Checkout Book</a></h3>
    <h3><a href="listbookhistory.php">Book Checkout History</a></h3>
  </body>
</html>