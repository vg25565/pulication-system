<?php
$servername = "localhost";
$username = "root";
$password = ""; // Default empty password for root user in XAMPP
$dbname = "publication";

function connectDatabase() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
?>
