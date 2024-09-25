<?php
session_start();
ob_start(); // Start output buffering
require 'conn.php'; // Ensure this file connects to the database
require 'send_email.php'; // Include the email sending file

// Create a database connection
$conn = connectDatabase();

// Initialize $data to avoid undefined variable warnings
$data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc(); // Fetch user data

    if ($data && password_verify($password, $data['password'])) {
        // Store user's ID and role in session
        $_SESSION['user_id'] = $data['id'];
        $_SESSION['user_name'] = isset($data['username']) ? $data['username'] : 'User'; 
        $_SESSION['role'] = $data['role'];

        // Generate a random OTP and expiry time
        $otp = rand(100000, 999999);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes')); // 15 minutes validity

        // Update the OTP and expiry time in the database
        $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
        $stmt->bind_param("ssi", $otp, $otp_expiry, $data['id']);
        $stmt->execute();

        // Email content
        $subject = 'Your OTP Code';
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

        // Send OTP email
        $emailResult = sendEmail($email, $subject, $body);
        if ($emailResult === true) {
            // Redirect to OTP verification page
            header("Location: otp_verification.php");
            exit(); // Ensure no further code runs after this
        } else {
            echo '<script>alert("Error sending OTP email: ' . htmlspecialchars($emailResult) . '");</script>';
        }
    } else {
        echo '<script>alert("Invalid Email or Password. Please try again.");</script>';
    }
}

// Close the database connection
$conn->close();
ob_end_flush(); // Send output buffer
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css"> <!-- External CSS file -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<header class="flex items-center justify-center p-4 bg-slate-100">
  <img src="tcet.png" alt="Logo" class="h-24 w-auto">
</header>

<div id="container" class="max-w-md mx-auto p-8 bg-white rounded-lg shadow-lg">
    <h1 class="text-2xl font-semibold text-center mb-6">Login</h1>
    <form method="post" action="" class="space-y-4">
        <div>
            <label for="email" class="block font-semibold mb-1">Email:</label>
            <input type="email" name="email" placeholder="Enter Your Email" required class="w-full px-4 py-2 border rounded-lg">
        </div>

        <div>
            <label for="password" class="block font-semibold mb-1">Password:</label>
            <input type="password" name="password" placeholder="Enter Your Password" required class="w-full px-4 py-2 border rounded-lg">
        </div>

        <input type="submit" name="login" value="Login" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600">
        <p class="text-center mt-4">Don't have an account? <a href="registration.php" class="text-blue-500 hover:underline">Sign Up</a></p>
    </form>
</div>
</body>
</html>
