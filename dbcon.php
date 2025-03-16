<?php
$servername = "localhost"; // Adjust this if needed

// Choose the correct credentials:
$username = "root"; // or "u507130350_root"
$password = ""; // or "Jirmygwapo123"
$dbname = "herb"; // or "u507130350_supply_db"

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
