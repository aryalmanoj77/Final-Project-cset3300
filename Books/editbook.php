<?php
  //echo "books/editbook.php page";
?>

<!DOCTYPE html>
<html>
  <head>
    <?php require('../inc-stdmeta.php'); ?>
    <title>Edit Book</title>
  </head>
  <body>
    <h1>Edit Book</h1>
    <h3><a href="listbooks.php">Back to Books</a></h3>
  </body>
</html>

                           Proposed Code for editbook.php:

<?php
// Include standard meta and database connection
require('../inc-stdmeta.php');
require('../dbconfig.php'); // Ensure this file connects to the database

// Initialize variables
$error = '';
$success = '';
$book = null;

// Check if a book ID is provided in the query string
if (isset($_GET['book_id']) && is_numeric($_GET['book_id'])) {
    $book_id = $_GET['book_id'];

    // Fetch the book details from the database
    $query = "SELECT * FROM books WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
    } else {
        $error = "Book not found.";
    }
    $stmt->close();
} else {
    $error = "Invalid book ID.";
}

// Handle form submission to update book details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $status = $_POST['status'];

    // Basic validation
    if (empty($title) || empty($author) || empty($genre) || empty($status)) {
        $error = "All fields are required.";
    } else {
        $update_query = "UPDATE books SET title = ?, author = ?, genre = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ssssi', $title, $author, $genre, $status, $book_id);

        if ($stmt->execute()) {
            $success = "Book details updated successfully.";
            // Refresh book details
            $book['title'] = $title;
            $book['author'] = $author;
            $book['genre'] = $genre;
            $book['status'] = $status;
        } else {
            $error = "Error updating book details: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Book</title>
</head>
<body>
    <h1>Edit Book</h1>

    <!-- Display errors or success messages -->
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <?php if ($book): ?>
        <!-- Book edit form -->
        <form method="post">
            <label for="title">Title:</label><br>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required><br><br>

            <label for="author">Author:</label><br>
            <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required><br><br>

            <label for="genre">Genre:</label><br>
            <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($book['genre']); ?>" required><br><br>

            <label for="status">Status:</label><br>
            <select id="status" name="status" required>
                <option value="available" <?php if ($book['status'] == 'available') echo 'selected'; ?>>Available</option>
                <option value="checked_out" <?php if ($book['status'] == 'checked_out') echo 'selected'; ?>>Checked Out</option>
            </select><br><br>

            <button type="submit" name="update">Update Book</button>
        </form>
    <?php else: ?>
        <p>Unable to load book details.</p>
    <?php endif; ?>

    <h3><a href="listbooks.php">Back to Books</a></h3>
</body>
</html>
