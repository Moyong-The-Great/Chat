<?php 
include 'dbcon.php';

session_start();

$loggedInUserId = $_SESSION['user_id'] ?? null;
$chatUserId = $_GET['user_id'] ?? null;

if (!$loggedInUserId || !$chatUserId || !is_numeric($chatUserId)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

// Secure query with prepared statements
$query = "SELECT message_id, content, sender_id, receiver_id, sent_at, status FROM messages 
          WHERE (sender_id = ? AND receiver_id = ?) 
          OR (sender_id = ? AND receiver_id = ?) 
          ORDER BY sent_at ASC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iiii", $loggedInUserId, $chatUserId, $chatUserId, $loggedInUserId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$messages = [];

while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = [
        "message_id" => $row['message_id'],
        "content" => htmlspecialchars($row['content']),
        "sender_id" => $row['sender_id'],
        "receiver_id" => $row['receiver_id'],
        "sent_at" => date("Y-m-d H:i:s", strtotime($row['sent_at'])), 
        "status" => $row['status'] // Include message status (read/unread)
    ];
}

// Mark messages as "read" if they were sent to the logged-in user
$updateQuery = "UPDATE messages SET status = 'read' 
                WHERE sender_id = ? AND receiver_id = ? AND status = 'unread'";

$updateStmt = mysqli_prepare($conn, $updateQuery);
mysqli_stmt_bind_param($updateStmt, "ii", $chatUserId, $loggedInUserId);
mysqli_stmt_execute($updateStmt);
mysqli_stmt_close($updateStmt);

header("Content-Type: application/json");
echo json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
