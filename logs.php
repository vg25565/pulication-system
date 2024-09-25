<?php
session_start(); // Start the session to track logged-in user

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}

// Include the email sending function
require 'send_email.php';

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "publication"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['pdf_upload'])) {
    $fileName = $_FILES['pdf_upload']['name'];
    $fileTmpName = $_FILES['pdf_upload']['tmp_name'];
    $uploadDir = 'uploads/'; // Ensure this directory exists and is writable
    $filePath = $uploadDir . basename($fileName);

    if (move_uploaded_file($fileTmpName, $filePath)) {
        // Insert file details into the database
        $insertQuery = "INSERT INTO uploads (user_id, file_name, file_path, status) VALUES (?, ?, ?, 'Left to Review')";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('iss', $_SESSION['user_id'], $fileName, $filePath);
        $stmt->execute();
        $stmt->close();

        // Fetch user info
        $userQuery = "SELECT username, email FROM users WHERE id = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $userResult = $stmt->get_result();
        $userInfo = $userResult->fetch_assoc();
        $stmt->close();

        // Email content for thank-you note
        $toEmail = $userInfo['email'];
        $subject = 'Thank You for Uploading Your PDF';
        $body = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                .container { width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1); }
                .header { display: flex; align-items: center; background-color: #4CAF50; padding: 20px; color: white; }
                .header img { max-width: 100px; margin-right: 20px; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { margin: 20px 0; font-size: 16px; line-height: 1.6; color: #333333; }
                .footer { text-align: center; padding: 10px; font-size: 12px; color: #777777; background-color: #f4f4f4; }
                .footer a { color: #4CAF50; text-decoration: none; }
            </style>
        </head>
        <body class="bg-gray-100">
            <div class="container">
                <div class="header">
                    <img src="https://admission.tcetmumbai.in/images/tcet-logo-home.jpg" alt="tcet logo" class="logo">
                    <h1 class="text-xl font-bold">Thank You for Your Submission</h1>
                </div>
                <div class="content">
                    <p class="mb-4">Dear ' . htmlspecialchars($userInfo['username']) . ',</p>
                    <p class="mb-4">Thank you for uploading your PDF. Your submission is now under review.</p>
                    <p class="mt-4">If you have any questions or need further assistance, please feel free to contact us.</p>
                </div>
                <div class="footer">
                    <p class="mb-2">Need help? <a href="https://yourwebsite.com/contact" class="text-green-600">Contact us</a></p>
                    <p class="text-gray-600">&copy; 2024 Your App. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';

        // Send thank-you email
        $emailResult = sendEmail($toEmail, $subject, $body);
        if ($emailResult === true) {
            echo 'Thank you email sent successfully';
        } else {
            echo 'Error: ' . $emailResult;
        }
    } else {
        echo 'File upload failed.';
    }
}

// Fetch count for each status
$query = "
    SELECT 
        COUNT(*) AS total_uploads,
        SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) AS Accepted,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected,
        SUM(CASE WHEN status = 'Left to Review' THEN 1 ELSE 0 END) AS pending
    FROM uploads
    WHERE user_id = ?; -- Filter by logged-in user
";

// Prepare and bind the statement
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $_SESSION['user_id']); // Bind the user_id from session
$stmt->execute();
$result = $stmt->get_result();

// Check for query execution errors
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Fetch the result as an associative array
$statusData = $result->fetch_assoc();

// Fetch log details
$logQuery = "
    SELECT 
        u.id AS upload_id, 
        u.file_name AS file_name,
        u.upload_date,
        u.status,
        f.faculty_name AS faculty,
        f.file_path
    FROM uploads u
    LEFT JOIN FacultyInformation f ON u.user_id = f.id
    WHERE u.user_id = ?; -- Filter by logged-in user
";

// Prepare and bind the statement
$logStmt = $conn->prepare($logQuery);
$logStmt->bind_param('i', $_SESSION['user_id']);
$logStmt->execute();
$logResult = $logStmt->get_result();

$logs = [];
if ($logResult) {
    while ($row = $logResult->fetch_assoc()) {
        $logs[] = $row;
    }
} else {
    die("Query failed: " . $conn->error);
}

// Output data as JSON
header('Content-Type: application/json');
echo json_encode(['status' => $statusData, 'logs' => $logs]);

// Close the result set and connection
$result->close();
$logResult->close();
$conn->close();
?>
