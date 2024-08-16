<?php
session_start();
include 'conn.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo '<script>
            alert("Registration Successful.");
            window.location.href = "index.php";
        </script>';
    } else {
        echo '<script>alert("Registration Failed. Try Again.");</script>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<header class="flex items-center justify-center p-4 bg-slate-100">
  <img src="tcet.png" alt="Logo" class="h-24 w-auto">
</header>

    <div id="registration-container">
        <h1>Register</h1>
        <form method="post" action="registration.php">
            <label for="username">Username:</label>
            <input type="text" name="username" placeholder="Enter Username" required>

            <label for="email">Email:</label>
            <input type="email" name="email" placeholder="Enter Your Email" required>

            <label for="password">Password:</label>
            <input type="password" name="password" placeholder="Enter Password" required>
            
            <input type="submit" name="register" value="Register">
            <p>Already have an account? <a href="index.php">Login</a></p>
        </form>
    </div>
</body>
</html>
