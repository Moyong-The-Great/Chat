<?php
include 'dbcon.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $password, $phone, $address, $role);

    if ($stmt->execute()) {
        // Redirect to index.php with success message
        header("Location: index.php?registration=success");
        exit();
    } else {
        // Redirect to index.php with an error message
        header("Location: index.php?registration=error");
        exit();
    }
    $stmt->close();
}
?>
