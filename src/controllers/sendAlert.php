<?php
require '../../database/db_connection.php';
require 'adminUser.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin = new AdminUser($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $message = $_POST['message'];
    $result = $admin->sendAlert($email, $message);

    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
}
?>