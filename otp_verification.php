<?php
session_start();

// Include the database connection function
require 'conn.php';

// Check if the user is logged in and OTP is set in the session
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $user_otp = $_POST['otp'];

    // Get the database connection
    $conn = connectDatabase();

    // Get the OTP, expiry time, and role from the database
    $stmt = $conn->prepare("SELECT otp, otp_expiry, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    // Debugging - Check the data fetched from the database
    // Uncomment this line if you need to see the debug information
    // var_dump($data);

    // Check if the OTP matches and is within the valid time
    if ($data && $data['otp'] == $user_otp && strtotime($data['otp_expiry']) > time()) {
        // OTP is valid
        $_SESSION['role'] = $data['role']; // Ensure role is set in session
        echo '<script>alert("OTP Verified Successfully!");</script>';

        // Redirect based on role
        if ($data['role'] == 'admin') {
            header("Location: admin.php"); // Redirect to admin page
        } elseif ($data['role'] == 'faculty') {
            header("Location: dashboard.php"); // Redirect to faculty dashboard or another page
        } else {
            // Handle other roles or default case
            header("Location: index.php"); // Redirect to login or default page
        }
        exit();
    } else {
        // OTP is invalid or expired
        echo '<script>alert("Invalid or Expired OTP. Please try again.");</script>';
    }

    // Close the database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<header class="flex items-center justify-center p-4 bg-slate-100">
    <img src="tcet.png" alt="Logo" class="h-24 w-auto">
</header>

<div id="container" class="max-w-md mx-auto p-8 bg-white rounded-lg shadow-lg">
    <h1 class="text-2xl font-semibold text-center mb-6">OTP Verification</h1>
    <form method="post" action="otp_verification.php" class="space-y-4">
        <div>
            <label for="otp" class="block font-semibold mb-1">Enter OTP:</label>
            <input type="text" name="otp" placeholder="Enter the OTP sent to your email" required class="w-full px-4 py-2 border rounded-lg">
        </div>
        <input type="submit" name="verify_otp" value="Verify OTP" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600">
    </form>
</div>
</body>
</html>
