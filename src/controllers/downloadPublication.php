<?php
require '../../database/db_connection.php';
require 'reviewSystem.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reviewSystem = new ReviewSystem($conn);

if (isset($_GET['publication_id'])) {
    $publication_id = intval($_GET['publication_id']);
    $reviewSystem->downloadPublication($publication_id);
}
?>
