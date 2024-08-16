<?php
include 'conn.php';

// Function to connect to the database
function connectDatabase() {
    global $host,$user,$pass,$db;
    $conn = new mysqli($host,$user,$pass,$db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Function to insert data into the database
function insertData($faculty_branch, $faculty_name, $faculty_data, $faculty_datetime, $file_path) {
    $conn = connectDatabase();
    $sql = "INSERT INTO FacultyInformation (faculty_branch, faculty_name, faculty_data, faculty_datetime, file_path) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $faculty_branch, $faculty_name, $faculty_data, $faculty_datetime, $file_path);

    if ($stmt->execute()) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

// Handling form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $faculty_branch = isset($_POST['faculty_branch']) ? $_POST['faculty_branch'] : die("Faculty Branch is not provided.");
    $faculty_name = isset($_POST['faculty_name']) ? $_POST['faculty_name'] : die("Faculty Name is not provided.");
    $faculty_data = isset($_POST['faculty_data']) ? $_POST['faculty_data'] : die("Faculty Data is not provided.");
    $faculty_datetime = isset($_POST['faculty_datetime']) ? $_POST['faculty_datetime'] : die("Faculty Datetime is not provided.");
    $file_path = 'uploads/' . basename($_FILES["file_upload"]["name"]);

    $allowed_types = ['application/pdf'];
    $file_type = mime_content_type($_FILES["file_upload"]["tmp_name"]);

    if (!in_array($file_type, $allowed_types)) {
        die("Invalid file type.");
    }

    if ($_FILES["file_upload"]["size"] > 5000000) {
        die("File is too large.");
    }

    if (!is_dir('uploads')) {
        if (!mkdir('uploads', 0755, true)) {
            die("Failed to create directory.");
        }
    }

    if (move_uploaded_file($_FILES["file_upload"]["tmp_name"], $file_path)) {
        insertData($faculty_branch, $faculty_name, $faculty_data, $faculty_datetime, $file_path);
    } else {
        echo "Sorry, there was an error uploading your file.";
        echo "Error details: ";
        print_r(error_get_last());
    }
}
?>
