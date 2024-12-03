<?php
  require('../isauthenticated.php');
  
  $title = $author = $publisher = "";
  $titleErr = $authorErr = $publisherErr = "";
  $success = false;
  $recentBook = null;

  // Get most recent book
  $inifile = parse_ini_file("../myproperties.ini");
  $conn = mysqli_connect($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"])
          or die("Connection failed: " . mysqli_connect_error());
          
  $recentBookQuery = "SELECT bookid, title, author, publisher, create_dt FROM book ORDER BY create_dt DESC LIMIT 1";
  $result = mysqli_query($conn, $recentBookQuery);
  $recentBook = mysqli_fetch_assoc($result);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate title
    if (empty($_POST["title"])) {
      $titleErr = "Title is required";
    } else {
      $title = CleanInput($_POST["title"]);
      if (strlen($title) > 50) {
        $titleErr = "Title must be 50 characters or less";
      }
    }
    
    // Validate author
    if (empty($_POST["author"])) {
      $authorErr = "Author is required";
    } else {
      $author = CleanInput($_POST["author"]);
      if (strlen($author) > 50) {
        $authorErr = "Author must be 50 characters or less";
      }
    }
    
    // Validate publisher
    if (empty($_POST["publisher"])) {
      $publisherErr = "Publisher is required";
    } else {
      $publisher = CleanInput($_POST["publisher"]);
      if (strlen($publisher) > 50) {
        $publisherErr = "Publisher must be 50 characters or less";
      }
    }
    
    // If no errors, insert into database
    if (empty($titleErr) && empty($authorErr) && empty($publisherErr)) {
      $sql = "INSERT INTO book (title, author, publisher) VALUES (?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $title, $author, $publisher);
      
      if ($stmt->execute()) {
        $success = true;
        $title = $author = $publisher = "";
        // Refresh recent book
        $result = mysqli_query($conn, $recentBookQuery);
        $recentBook = mysqli_fetch_assoc($result);
      }
      
      $stmt->close();
    }
  }
  
  $conn->close();
  
  function CleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }

  function GetFormattedDate($string) {
    if(isset($string)) {
      if(!empty($string)) {
        $date = strtotime($string);
        if($date !== false) return date('Y-M-d', $date);
        return htmlspecialchars($string);
      }
    }
    return "No Date Found";
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php require('../inc-stdmeta.php'); ?>
    <title>Add Book</title>
  </head>
  <body>
    <h1>Add Book</h1>
    <h3>CSET Department Student Library</h3>
    
    <?php if ($success): ?>
      <p class="success">Book successfully added to the library.</p>
    <?php endif; ?>
    
    <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <table>
        <tr>
          <td><label for="title">Title:</label></td>
          <td>
            <input type="text" id="title" name="title" value="<?= $title; ?>" maxlength="50">
            <span class="error"><?= $titleErr; ?></span>
          </td>
        </tr>
        <tr>
          <td><label for="author">Author:</label></td>
          <td>
            <input type="text" id="author" name="author" value="<?= $author; ?>" maxlength="50">
            <span class="error"><?= $authorErr; ?></span>
          </td>
        </tr>
        <tr>
          <td><label for="publisher">Publisher:</label></td>
          <td>
            <input type="text" id="publisher" name="publisher" value="<?= $publisher; ?>" maxlength="50">
            <span class="error"><?= $publisherErr; ?></span>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <input type="submit" value="Add Book">
          </td>
        </tr>
      </table>
    </form>

    <?php if ($recentBook): ?>
    <h2>Most Recently Added Book</h2>
    <table>
      <tr>
        <th>Book ID</th>
        <th>Title</th>
        <th>Author</th>
        <th>Publisher</th>
        <th>Added Date</th>
      </tr>
      <tr>
        <td style="text-align: center"><?= htmlspecialchars($recentBook['bookid']); ?></td>
        <td><?= htmlspecialchars($recentBook['title']); ?></td>
        <td><?= htmlspecialchars($recentBook['author']); ?></td>
        <td><?= htmlspecialchars($recentBook['publisher']); ?></td>
        <td style="text-align: center"><?= GetFormattedDate($recentBook['create_dt']); ?></td>
      </tr>
    </table>
    <?php endif; ?>
    
    <h3><a href="listbooks.php">Back to Books</a></h3>
  </body>
</html>