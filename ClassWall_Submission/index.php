<?php
session_start();

// Count the visitor's visits
if (!isset($_SESSION['visits'])) {
    $_SESSION['visits'] = 0;
}
$_SESSION['visits']++;

// Connect to PostgreSQL
require __DIR__ . '/db.php';

// Get remembered name from cookie
$author = $_COOKIE['remembered_name'] ?? '';
$body = '';
$error = '';

$rememberChecked = isset($_COOKIE['remembered_name']);

// Handle message submission using POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $author = trim($_POST['author'] ?? '');
    $body = trim($_POST['body'] ?? '');

    $rememberChecked = isset($_POST['remember']);

    // Remember or forget the visitor's name
    if ($rememberChecked && $author !== '') {
        setcookie('remembered_name', $author, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        setcookie('remembered_name', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    // Check if fields are empty
    if ($author === '' || $body === '') {
        $error = "Please enter your name and message.";
    } else {

        // Save message using prepared statement
        $sql = "INSERT INTO messages (author, body)
                VALUES (:author, :body)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':author' => $author,
            ':body' => $body
        ]);

        // Redirect after successful submission
        header("Location: index.php");
        exit;
    }
}

// Get search keyword using GET
$search = trim($_GET['search'] ?? '');

// Retrieve messages
try {

    if ($search !== '') {

        $sql = "SELECT * FROM messages
                WHERE body ILIKE :search
                ORDER BY created_at DESC";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':search' => '%' . $search . '%'
        ]);

    } else {

        $sql = "SELECT * FROM messages
                ORDER BY created_at DESC";

        $stmt = $pdo->query($sql);
    }

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Search error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));

}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassWall - Message Board</title>
</head>

<body>

    <h1>ClassWall</h1>
    <p>Welcome to our class message board!</p>

    <p>
        You've visited <?php echo $_SESSION['visits']; ?> times.
    </p>

    <hr>

    <h2>Post a Message</h2>

    <?php if ($error !== ''): ?>
        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST" action="index.php">

        <label>Your Name:</label><br>

        <input
            type="text"
            name="author"
            value="<?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?>"
            required
        >

        <br><br>

        <label>Your Message:</label><br>

        <textarea name="body" required><?php
            echo htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        ?></textarea>

        <br><br>

        <input
            type="checkbox"
            name="remember"
            value="yes"
            <?php echo $rememberChecked ? 'checked' : ''; ?>
        >

        <label>Remember my name</label>

        <br><br>

        <button type="submit">Post Message</button>

    </form>

    <hr>

    <h2>Search Messages</h2>

    <form method="GET" action="index.php">

        <input
            type="text"
            name="search"
            placeholder="Search messages..."
            value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
        >

        <button type="submit">Search</button>

        <a href="index.php">Show All</a>

    </form>

    <hr>

    <h2>Message Wall</h2>

    <?php if (count($messages) > 0): ?>

        <?php foreach ($messages as $message): ?>

            <div>

                <h3>
                    <?php echo htmlspecialchars($message['author'], ENT_QUOTES, 'UTF-8'); ?>
                </h3>

                <p>
                    <?php echo nl2br(htmlspecialchars($message['body'], ENT_QUOTES, 'UTF-8')); ?>
                </p>

                <small>
                    <?php echo htmlspecialchars($message['created_at'], ENT_QUOTES, 'UTF-8'); ?>
                </small>

                <hr>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p>No messages found.</p>

    <?php endif; ?>

</body>
</html>
