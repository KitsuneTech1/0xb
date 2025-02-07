<?php
header('Content-Type: application/json');

// === Database configuration ===
$host = 'localhost';
$db   = 'link_shortener';     // Change to your database name
$user = 'db_username';        // Change to your database username
$pass = 'db_password';        // Change to your database password

// Connect to MySQL using PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check if a URL was submitted
if (isset($_POST['original_url'])) {
    $original_url = trim($_POST['original_url']);
    
    // Validate the URL
    if (!filter_var($original_url, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid URL provided.']);
        exit;
    }

    // Function to generate a random short code
    function generateShortCode($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $shortCode = '';
        for ($i = 0; $i < $length; $i++) {
            $shortCode .= $characters[rand(0, $charactersLength - 1)];
        }
        return $shortCode;
    }

    // Ensure the generated code is unique
    do {
        $short_code = generateShortCode();
        $stmt = $pdo->prepare("SELECT id FROM urls WHERE short_code = ?");
        $stmt->execute([$short_code]);
        $exists = $stmt->fetch();
    } while ($exists);

    // Insert the new short URL mapping into the database
    $stmt = $pdo->prepare("INSERT INTO urls (short_code, original_url) VALUES (?, ?)");
    if ($stmt->execute([$short_code, $original_url])) {
        // Build the full short URL (update your domain if necessary)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https" : "http");
        $domain = $_SERVER['HTTP_HOST'];
        $short_url = $protocol . "://" . $domain . "/" . $short_code;
        echo json_encode(['success' => true, 'short_url' => $short_url]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create short URL.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No URL provided.']);
}
?>
