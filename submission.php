<?php
session_start();
require 'conn.php'; // Database connection
require 'send_email.php'; // Email sending functionality

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Fetch user data from the session
$userId = $_SESSION['user_id'];
$email = $_SESSION['email'] ?? ''; // Fetch email from the session if set

// Check if email is set
if (empty($email)) {
    echo "Error: Email not set in session.";
    exit();
}

// Check if the form was submitted and a file was uploaded
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['pdf_upload'])) {

    // Fetch form data
    $titleName = $_POST['faculty_data'] ?? '';
    $branch = $_POST['faculty_branch'] ?? '';
    $facultyName = $_POST['faculty_name'] ?? '';

    // Validate the form inputs
    if (empty($titleName) || empty($branch) || empty($facultyName)) {
        echo "Error: All form fields must be filled out.";
        exit();
    }

    // File upload processing
    $target_dir = "uploads/";
    $fileExtension = strtolower(pathinfo($_FILES["pdf_upload"]["name"], PATHINFO_EXTENSION));

    // Check if the uploaded file is a PDF
    if ($fileExtension != "pdf") {
        echo "Sorry, only PDF files are allowed.";
        exit();
    }

    // Generate a unique file name
    $randomNumber = rand(1000, 9999);
    $newFileName = $titleName . $randomNumber . '.' . $fileExtension;
    $target_file = $target_dir . $newFileName;

    // Move the uploaded file to the target directory
    if (move_uploaded_file($_FILES["pdf_upload"]["tmp_name"], $target_file)) {
        // Insert the uploaded file info into the database
        $conn = connectDatabase();
        $stmt = $conn->prepare("INSERT INTO uploads (user_id, file_name, status) VALUES (?, ?, 'submitted')");
        $stmt->bind_param("is", $userId, $newFileName);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        // Prepare the email content
        $subject = 'Thank You for Uploading Your Paper';

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
                .otp-code { font-size: 22px; font-weight: bold; color: #4CAF50; text-align: center; margin: 20px 0; }
                .footer { text-align: center; padding: 10px; font-size: 12px; color: #777777; background-color: #f4f4f4; }
                .footer a { color: #4CAF50; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                     <img src="https://admission.tcetmumbai.in/images/tcet-logo-home.jpg" alt="tcet logo" class="logo">
                    <h1>OTP Verification</h1>
                </div>
                <div class="content">
                    <p>Hello,</p>
                    <p>We received a request to log in to your account. Use the following OTP code to complete your login:</p>
                    <div class="otp-code">' . $otp . '</div>
                    <p>This OTP is valid for 15 minutes. If you did not request this, please ignore this email or contact our support team.</p>
                </div>
                <div class="footer">
                    <p>Need help? <a href="https://yourwebsite.com/contact">Contact us</a></p>
                    <p>&copy; 2024 Your App. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        // Send the email and check the result
        $emailResult = sendEmail($email, $subject, $body);
        if ($emailResult === true) {
            // File uploaded and email sent successfully
            echo 'File uploaded and thank you email sent successfully.';
            header('Location: logs.html'); // Redirect to logs after successful upload
            exit();
        } else {
            // Handle email sending error
            echo 'Error sending email: ' . $emailResult;
        }
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
} else {
    echo "No file uploaded or invalid form submission.";
}
?>
