<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['temp_user'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_otp = $_POST['otp'];
    $stored_otp = $_SESSION['temp_user']['otp'];
    $user_id = $_SESSION['temp_user']['id'];

    $stmt = $conn->prepare("SELECT otp_expiry FROM users WHERE id = ? AND otp = ?");
    $stmt->bind_param("is", $user_id, $user_otp);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        $otp_expiry = strtotime($data['otp_expiry']);
        if ($otp_expiry >= time()) {
            $_SESSION['user_id'] = $user_id;
            unset($_SESSION['temp_user']);
            header("Location: dashboard.php");
            exit();
        } else {
            echo '<script>
                alert("OTP has expired. Please try again.");
                window.location.href = "index.php";
            </script>';
        }
    } else {
        echo '<script>alert("Invalid OTP. Please try again.");</script>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div id="otp-container">
        <h1>Two-Step Verification</h1>
        <p>Enter the 6 Digit OTP Code that has been sent to your email address: <?php echo htmlspecialchars($_SESSION['email']); ?></p>
        <form method="post" action="otp_verification.php">
            <label for="otp">Enter OTP Code:</label>
            <input type="number" name="otp" pattern="\d{6}" placeholder="Six-Digit OTP" required>
            <button type="submit">Verify OTP</button>
        </form>
    </div>
</body>
</html>
