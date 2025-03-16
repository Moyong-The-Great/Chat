<?php
session_start();
require 'dbcon.php'; // Ensure database connection

header("Content-Type: application/json");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "User not authenticated"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['user_id']) || !isset($input['message'])) {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = intval($input['user_id']);
$message = trim($input['message']);

if (empty($message)) {
    echo json_encode(["success" => false, "error" => "Message cannot be empty"]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, sent_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$sender_id, $receiver_id, $message]);
    
    // Fetch the latest messages after sending
    $fetchStmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY sent_at ASC");
    $fetchStmt->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
    $messages = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "messages" => $messages]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Failed to send message: " . $e->getMessage()]);
}
