<?php
session_start(); // Start the session

// Include database connection and other necessary files
require 'conn.php'; // Database connection
require 'send_email.php'; // If you need to send emails

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirect to login if not logged in
    exit();
}

$user_id = $_SESSION['user_id']; // Get user ID from session

// Create a database connection
$conn = connectDatabase();

// Handle form submission for grievance
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_grievance'])) {
        $grievance_text = $_POST['grievance'];
        $stmt = $conn->prepare("INSERT INTO grievances (faculty_id, grievance_text) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $grievance_text);
        $stmt->execute();
        $stmt->close();

        // Redirect to the same page with a success message
        header('Location: grievance.php?msg=Grievance submitted successfully');
        exit();
    } elseif (isset($_POST['delete_grievance'])) {
        $grievance_id = $_POST['grievance_id'];
        $stmt = $conn->prepare("DELETE FROM grievances WHERE id = ? AND faculty_id = ?");
        $stmt->bind_param("ii", $grievance_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // Redirect to the same page with a success message
        header('Location: grievance.php?msg=Grievance deleted successfully');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Grievance Dashboard</title>
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 text-gray-800 flex flex-col h-screen">

    <!-- Sidebar -->
    <div class="flex flex-1">
        <aside class="bg-gray-800 text-white w-64 p-4">
            <h2 class="text-2xl font-bold mb-6">Dashboard</h2>
            <!-- Navigation links -->
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="block py-2 px-4 rounded-md hover:bg-gray-700">Home</a></li>
                    <li><a href="logs.html" class="block py-2 px-4 rounded-md hover:bg-gray-700">Logs</a></li>
                    <li><a href="grievance.php" class="block py-2 px-4 rounded-md hover:bg-gray-700">Grievance</a></li>
                    <li><a href="alerts.html" class="block py-2 px-4 rounded-md hover:bg-gray-700">Alerts</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <header class="relative flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold">Grievance Dashboard</h1>

                <!-- Profile Icon with Dropdown Menu -->
                <div class="relative">
                    <button onclick="toggleDropdown()" class="flex items-center space-x-2 focus:outline-none">
                        <i class="fas fa-user-circle text-2xl text-gray-800"></i>
                        <i class="fas fa-caret-down text-gray-800"></i>
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-64 bg-white border border-gray-300 rounded-lg shadow-lg hidden">
                        <div class="p-4 border-b border-gray-300">
                            <div class="flex items-center space-x-4">
                                <i class="fas fa-user-circle text-4xl text-gray-800"></i>
                                <div>
                                    <p class="text-lg font-semibold">Faculty</p>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Dropdown menu links -->
                        <ul>
                            <li><a href="profile.html" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">View Profile</a></li>
                            <li><a href="account.html" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Account Settings</a></li>
                            <li><a href="#" onclick="logout()" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Grievance Submission Form -->
            <section class="mb-6">
                <div class="bg-white p-6 rounded-lg shadow-md w-full">
                    <h3 class="text-xl font-semibold mb-2">Raise a Grievance</h3>
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="bg-green-100 text-green-800 p-3 mb-4 rounded">
                            <?php echo htmlspecialchars($_GET['msg']); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <textarea name="grievance" class="border border-black rounded-md p-2 w-full mb-4" placeholder="Enter your grievance" required></textarea>
                        <button type="submit" name="submit_grievance" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600 focus:outline-none w-full">Submit</button>
                    </form>
                </div>
            </section>

            <!-- Faculty's Grievances -->
            <section class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h2 class="text-2xl font-semibold mb-4">Your Grievances</h2>
                <?php
                // Fetch grievances for the logged-in faculty
                $sql = "SELECT id, grievance_text, created_at FROM grievances WHERE faculty_id = ? ORDER BY created_at DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $grievance_id = $row['id'];
                        $grievance_text = $row['grievance_text'];
                        $created_at = date('F j, Y, g:i a', strtotime($row['created_at'])); // Format the date and time

                        echo "<div class='bg-white p-4 mb-4 rounded-md shadow-sm'>
                                <p>{$grievance_text}</p>
                                <p class='text-sm text-gray-600'>Submitted on: {$created_at}</p>
                                <form method='POST' action='' onsubmit='return confirmDelete()'>
                                    <input type='hidden' name='grievance_id' value='{$grievance_id}' />
                                    <button type='submit' name='delete_grievance' class='bg-red-500 text-white px-2 py-1 rounded-md hover:bg-red-600 focus:outline-none'>Delete</button>
                                </form>
                              </div>";
                    }
                } else {
                    echo "<p class='text-gray-700'>No grievances found.</p>";
                }
                $stmt->close();
                ?>
            </section>
        </main>
    </div>

    <script>
        // Function to log out the user
        function logout() {
            window.location.href = 'index.php'; // Redirect back to login page
        }

        // Function to toggle the profile dropdown menu
        function toggleDropdown() {
            const dropdown = document.getElementById('profile-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Function to confirm grievance deletion
        function confirmDelete() {
            return confirm("Are you sure you want to delete this grievance?");
        }
    </script>
</body>
</html>
