<?php
require 'conn.php';
require 'reviewSystem.php';

$conn = new mysqli($host,$user,$pass,$db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reviewSystem = new ReviewSystem($conn);
$facultyWithoutPublications = $reviewSystem->getFacultyWithoutPublications();

header('Content-Type: application/json');
echo json_encode($facultyWithoutPublications);
?>