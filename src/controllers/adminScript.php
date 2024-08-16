<?php
require '../../database/db_connection.php'; 
require 'adminUser.php'; 
require 'reviewSystem.php';

$admin = new AdminUser($conn);
$reviewSystem = new ReviewSystem($conn);

$loginResult = $admin->login('adminUsername', 'adminPassword');
if ($loginResult['success']) {
    echo "Logged in successfully. Admin ID: " . $loginResult['admin_id'];
} else {
    echo "Login failed: " . $loginResult['message'];
}

// Ajax GET and POST Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $publicationId = $_POST['publicationId'];

    if ($action === 'approve') {
        echo json_encode($reviewSystem->approvePublication($publicationId));
    } elseif ($action === 'reject') {
        echo json_encode($reviewSystem->rejectPublication($publicationId));
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'getPublications') {
        $faculty_id = $_GET['faculty_id'];
        $publications = $reviewSystem->viewPublications($faculty_id);
        header('Content-Type: application/json');
        echo json_encode($publications);
    } elseif (isset($_GET['action']) && $_GET['action'] === 'getFacultyWithoutPublications') {
        $facultyWithoutPublications = $reviewSystem->getFacultyWithoutPublications();
        header('Content-Type: application/json');
        echo json_encode($facultyWithoutPublications);
    }
    exit();
}

// $publications = $reviewSystem->viewPublications(1); 
// print_r($publications);

// $reviewSystem->downloadPublication(1); 

// $reviewResult = $reviewSystem->approvePublication(1); 
// if ($reviewResult['success']) {
//     echo "Publication approved successfully.";
// } else {
//     echo "Failed to approve publication: " . $reviewResult['message'];
// }


// $facultyWithoutPublications = $reviewSystem->checkFacultyPublications();
// foreach ($facultyWithoutPublications as $faculty) {
//     $admin->sendAlert($faculty['email'], "You have not published any papers in the last month. Please submit your publications.");
// }
?>
