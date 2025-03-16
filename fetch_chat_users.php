<?php
include('db.php'); // Include database connection

header('Content-Type: application/json');

$query = "
    SELECT u.user_id, u.name, u.email, u.role, 
           (SELECT m.content FROM messages m WHERE (m.sender_id = u.user_id OR m.receiver_id = u.user_id) ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
           (SELECT m.sent_at FROM messages m WHERE (m.sender_id = u.user_id OR m.receiver_id = u.user_id) ORDER BY m.sent_at DESC LIMIT 1) AS last_message_time
    FROM users u
";

$result = mysqli_query($conn, $query);
$users = [];

while ($row = mysqli_fetch_assoc($result)) {
    $users[] = [
        "user_id" => $row['user_id'],
        "name" => $row['name'],
        "email" => $row['email'],
        "role" => $row['role'],
        "last_message" => $row['last_message'] ?? null,
        "last_message_time" => $row['last_message_time'] ?? null
    ];
}

echo json_encode($users);
?>
