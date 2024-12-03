<?php
  require('../isauthenticated.php');
  
  $title = $author = $publisher = $active = "";
  $titleErr = $authorErr = $publisherErr = "";
  $success = false;
  $bookid = "";
  $book = null;

  // Get bookid from either GET or POST
  if(isset($_GET['bookid'])) {
    $bookid = CleanInput($_GET['bookid']);
  } elseif(isset($_POST['bookid'])) {
    $bookid = CleanInput($_POST['bookid']);
  }

  // Connect to database once
  $inifile = parse_ini_file("../myproperties.ini");
  $conn = mysqli_connect($inifile["DBHOST"], $inifile["DBUSER"], $inifile["DBPASS"], $inifile["DBNAME"])
          or die("Connection failed: " . mysqli_connect_error());

  // Process POST request first if it exists
  if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bookid'])) {
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
    
    // Get active status
    $active = isset($_POST["active"]) ? 1 : 0;
    
    // If no errors, update database
    if (empty($titleErr) && empty($authorErr) && empty($publisherErr)) {
      $sql = "UPDATE book SET title = ?, author = ?, publisher = ?, active = ? WHERE bookid = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssii", $title, $author, $publisher, $active, $bookid);
      
      if ($stmt->execute()) {
        $success = true;
      }
      
      $stmt->close();
    }
  }

  // Get book information if bookid exists (either from GET or POST)
  if($bookid) {
    $sql = "SELECT bookid, title, author, publisher, active FROM book WHERE bookid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookid);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    
    if($book) {
      $title = $book['title'];
      $author = $book['author'];
      $publisher = $book['publisher'];
      $active = $book['active'];
    }
    
    $stmt->close();
  }

  // Close database connection
  $conn->close();

  function CleanInput($data) {
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
    <title>Edit Book</title>
  </head>
  <body>
    <h1>Edit Book</h1>
    <h3>CSET Department Student Library</h3>
    
    <?php if (!$book && $bookid): ?>
      <p class="error">Book not found.</p>
      <h3><a href="listbooks.php">Back to Books</a></h3>
    <?php else: ?>
    
      <?php if ($success): ?>
        <p class="success">Book successfully updated.</p>
      <?php endif; ?>
      
      <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <input type="hidden" name="bookid" value="<?= htmlspecialchars($bookid); ?>">
        <table>
          <tr>
            <td><label for="title">Title:</label></td>
            <td>
              <input type="text" id="title" name="title" value="<?= htmlspecialchars($title); ?>" maxlength="50">
              <span class="error"><?= $titleErr; ?></span>
            </td>
          </tr>
          <tr>
            <td><label for="author">Author:</label></td>
            <td>
              <input type="text" id="author" name="author" value="<?= htmlspecialchars($author); ?>" maxlength="50">
              <span class="error"><?= $authorErr; ?></span>
            </td>
          </tr>
          <tr>
            <td><label for="publisher">Publisher:</label></td>
            <td>
              <input type="text" id="publisher" name="publisher" value="<?= htmlspecialchars($publisher); ?>" maxlength="50">
              <span class="error"><?= $publisherErr; ?></span>
            </td>
          </tr>
          <tr>
            <td><label for="active">In Circulation:</label></td>
            <td>
              <input type="checkbox" id="active" name="active" <?= $active ? "checked" : ""; ?>>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <input type="submit" value="Update Book">
            </td>
          </tr>
        </table>
      </form>
      
    <?php endif; ?>
    
    <h3><a href="listbooks.php">Back to Books</a></h3>
  </body>
</html>