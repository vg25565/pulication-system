<?php
session_start();
include 'conn.php';
require 'vendor/autoload.php'; // This includes PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $_SESSION['email'] = $email;

    // Check for admin login
    if ($email === 'examplemail' && $password === 'admin') {
        $_SESSION['admin'] = true;
        header("Location: dashboard.html");
        exit();
    }

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data && password_verify($password, $data['password'])) {
        $otp = rand(100000, 999999);
        $otp_expiry = date("Y-m-d H:i:s", strtotime("+3 minutes"));
        $subject = "Your OTP for Login";
        $message = "Your OTP is: $otp";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = ''; // Your email address
            $mail->Password = ''; // Your email app password
            $mail->Port = 587;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->isHTML(true);
            $mail->setFrom('example@gmail.com', 'Publication System');
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
        } catch (Exception $e) {
            echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}');</script>";
            exit();
        }

        $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
        $stmt->bind_param("ssi", $otp, $otp_expiry, $data['id']);
        $stmt->execute();

        $_SESSION['temp_user'] = ['id' => $data['id'], 'otp' => $otp];
        header("Location: otp_verification.php");
        exit();
    } else {
        echo '<script>
            alert("Invalid Email or Password. Please try again.");
            window.location.href = "index.php";
        </script>';
    }
}
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


    <div id="container">
        <h1>Login</h1>
        <form method="post" action="index.php">
            <label for="email">Email</label>
            <input type="email" name="email" placeholder="Enter Your Email" required>

            <label for="password">Password:</label>
            <input type="password" name="password" placeholder="Enter Your Password" required>

            <input type="submit" name="login" value="Login">
            <p>Don't have an account? <a href="registration.php">Sign Up</a></p>
        </form>
    </div>
</body>
</html>
