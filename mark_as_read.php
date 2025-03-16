<?php
session_start();
require 'dbcon.php'; // Database connection

if (!isset($_SESSION['user_id'])) {
    exit;
}

$loggedInUserId = $_SESSION['user_id'];
$senderId = $_POST['user_id'] ?? null;

if (!$senderId) {
    exit;
}

// Update all messages from `senderId` to `loggedInUserId` as "read"
$stmt = $pdo->prepare("
    UPDATE messages 
    SET status = 'read' 
    WHERE sender_id = ? AND receiver_id = ? AND status = 'unread'
");
$stmt->execute([$senderId, $loggedInUserId]);

echo json_encode(["status" => "success"]);
?>
