<?php
include 'conn.php';

// Create a database connection
$conn = connectDatabase();

// Allow access to registration page only if the user is not logged in
// if (isset($_SESSION['user_id'])) {
//     echo '<script>alert("You are already logged in. Please log out to register a new account.");</script>';
//     header("Location: index.php");
//     exit();
// }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role']; // Get the selected role

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepare the SQL statement to insert user data
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
    if ($stmt->execute()) {
        // Registration successful, redirect to login
        header("Location: index.php");
        exit();
    } else {
        echo '<script>alert("Registration Failed. Try Again.");</script>';
    }
}

// Close the database connection
$conn->close();
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

<div id="registration-container" class="max-w-md mx-auto p-8 bg-white rounded-lg shadow-lg">
    <h1 class="text-2xl font-semibold text-center mb-6">Register</h1>
    <form method="post" action="" class="space-y-4">
        <div>
            <label for="username" class="block font-semibold mb-1">Username:</label>
            <input type="text" name="username" placeholder="Enter Username" required class="w-full px-4 py-2 border rounded-lg">
        </div>

        <div>
            <label for="email" class="block font-semibold mb-1">Email:</label>
            <input type="email" name="email" placeholder="Enter Your Email" required class="w-full px-4 py-2 border rounded-lg">
        </div>

        <div>
            <label for="password" class="block font-semibold mb-1">Password:</label>
            <input type="password" name="password" placeholder="Enter Password" required class="w-full px-4 py-2 border rounded-lg">
        </div>

        <div>
            <label for="role" class="block font-semibold mb-1">Role:</label>
            <select name="role" required class="w-full px-4 py-2 border rounded-lg">
                <option value="admin">Admin</option>
                <option value="faculty">Faculty</option>
            </select>
        </div>

        <input type="submit" name="register" value="Register" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600">
        <p class="text-center mt-4">Already have an account? <a href="login.php" class="text-blue-500 hover:underline">Login</a></p>
    </form>
</div>
</body>
</html>
