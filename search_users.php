<?php
include 'dbcon.php';

$sql = "SELECT user_id, name FROM users WHERE role IN ('customer', 'delivery','herbal_specialist')";
$result = $conn->query($sql);

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode($users);
?>
