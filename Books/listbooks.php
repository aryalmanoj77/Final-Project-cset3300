<?php
  session_start();
  //Show only active, only inactive, or both.
  $filteractive = "";
  //Show only checkouts, not checked out, or both.
  $filtercheckout = "";
  //Column to order by.
  $sortcol = "";
  //Ascending or descending for sortcol.
  $sortdir = "";

  //Check for filtering criteria
  //do filter stuff here

  //Check for sort ordering criteria
  if($_SERVER['REQUEST_METHOD']=="GET" && isset($_GET['sortcol'])){
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
      case 'rocketid':
        $sortcol .= "`rocketid`";
        break;
      case 'name':
        $sortcol .= "`name`";
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
    $sessionkey_sort = "sortdirection";
    $sortdir = "ASC";
    if(isset($_SESSION[$sessionkey_sort])){
      switch($_SESSION[$sessionkey_sort]){
        case 'ASC':
          $sortdir = "DESC";
          break;
        case 'DESC':
        default:
          $sortdir = "ASC";
      }
    }
    $_SESSION[$sessionkey_sort] = $sortdir;
  }
  $inifile = parse_ini_file("../myproperties.ini");
  $conn = mysqli_connect($inifile["DBHOST"],$inifile["DBUSER"],$inifile["DBPASS"],$inifile["DBNAME"])
          or die("Connection failed:" . mysqli_connect_error());
	//$sql = "SELECT `name`, `email`, `phone`, `bgroup`, `id` FROM `users` $sortcol $sortdir";
  $sql = "SELECT`bookid`,`title`,`author`,`publisher`,`active`,`checkoutid`,`rocketid`,`name`,`promise_date`,`return_date`";
  $sql .= "FROM`master_book_query`";
  $sql .= "$filteractive $filtercheckout $sortcol $sortdir";
  $resultset = mysqli_query($conn,$sql);
  $books = mysqli_fetch_all($resultset,MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
  <head>
    <?php require('../inc-stdmeta.php'); ?>
    <title>Books</title>
  </head>
  <body>
    <h1>Books</h1>
    <table>
      <tr>
        <th><a href="listbooks.php?sortcol=bookid">Book ID</a></th>
        <th><a href="listbooks.php?sortcol=title">Title</a></th>
        <th><a href="listbooks.php?sortcol=author">Author</a></th>
        <th><a href="listbooks.php?sortcol=publisher">Publisher</a></th>
        <th><a href="listbooks.php?sortcol=rocketid">Rocket ID</a></th>
        <th><a href="listbooks.php?sortcol=name">Name</a></th>
        <th><a href="listbooks.php?sortcol=promise_date">Promise Date</a></th>
        <th><a href="listbooks.php?sortcol=return_date">Return Date</a></th>
        <th><a>Check Out</a></th>
        <th><a>Return</a></th>
      </tr>
      <?php foreach($books as $book): ?>
      <tr>
        <td><?= $book['bookid']; ?></td>
        <td><a href="editbook.php?bookid=<?= $book['bookid']; ?>"><?= htmlspecialchars($book['title']); ?></a></td>
        <td><?= htmlspecialchars($book['author']); ?></td>
        <td><?= htmlspecialchars($book['publisher']); ?></td>
        <td><?= htmlspecialchars($book['rocketid']); ?></td>
        <td><?= htmlspecialchars($book['name']); ?></td>
        <td><?= htmlspecialchars($book['promise_date']); ?></td>
        <td><?= htmlspecialchars($book['return_date']); ?></td>
        <td><a href="checkoutbook.php?<?= $book['bookid']; ?>">Check Out</a></td>
        <td><a href="returnbook.php?<?= $book['bookid']; ?>">Return</a></td>
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