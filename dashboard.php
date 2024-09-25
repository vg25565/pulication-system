<?php
session_start();
require 'conn.php';
require 'send_email.php'; // Import the send_email.php file

// Function to fetch user info
function getUserInfo($userId) {
    $conn = connectDatabase();
    $sql = "SELECT username, email FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $user;
}

// Function to count PDFs uploaded by the user
function countUploadedPDFs($userId) {
    $conn = connectDatabase();
    $sql = "SELECT COUNT(*) AS pdf_count FROM uploads WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $count['pdf_count'];
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userInfo = getUserInfo($userId);
$_SESSION['email'] = $userInfo['email'];  // Set session email here
$pdfCount = countUploadedPDFs($userId);

// Handle file upload logic will be in submission.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 flex flex-col h-screen">

    <!-- Main container for sidebar and content -->
    <div class="flex flex-1">
        <!-- Sidebar -->
        <aside class="bg-gray-800 text-white w-64 p-4">
            <h2 class="text-2xl font-bold mb-6">Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="block py-2 px-4 rounded-md hover:bg-gray-700">Home</a></li>
                    <li><a href="logs.html" class="block py-2 px-4 rounded-md hover:bg-gray-700">Logs</a></li>
                    <li><a href="Grievance.php" class="block py-2 px-4 rounded-md hover:bg-gray-700">Grievance</a></li>
                    <li><a href="alerts.html" class="block py-2 px-4 rounded-md hover:bg-gray-700">Alerts</a></li>   
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 p-6">
            <header class="relative flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold">Welcome</h1>
                <div class="relative">
                    <!-- Profile Icon with Dropdown Menu -->
                    <button onclick="toggleDropdown()" class="flex items-center space-x-2 focus:outline-none">
                        <i class="fas fa-user-circle text-2xl text-gray-800"></i>
                        <i class="fas fa-caret-down text-gray-800"></i>
                    </button>
                    <!-- Dropdown menu -->
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-64 bg-white border border-gray-300 rounded-lg shadow-lg hidden">
                        <div class="p-4 border-b border-gray-300">
                            <div class="flex items-center space-x-4">
                                <i class="fas fa-user-circle text-4xl text-gray-800"></i>
                                <div>
                                    <p class="text-lg font-semibold"><?php echo htmlspecialchars($userInfo['username']); ?></p>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($userInfo['email']); ?></p>
                                </div>
                            </div>
                        </div>
                        <ul>
                            <!-- Dropdown menu links -->
                            <li><a href="profile.html" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">View Profile</a></li>
                            <li><a href="account.html" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Account Settings</a></li>
                            <li><a href="#" onclick="logout()" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Dashboard Overview Section -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h2 class="text-2xl font-semibold mb-4">Overview</h2>
                <p class="text-gray-700 mb-4">Here you can manage users, view reports, and configure system settings.</p>

                <!-- Cards for quick overview -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-blue-100 p-4 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold">User Statistics</h3>
                        <p class="text-gray-600">Total PDFs Uploaded: <?php echo $pdfCount; ?></p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold">System Settings</h3>
                        <p class="text-gray-600">Manage system configurations and settings.</p>
                    </div>
                    <div class="bg-yellow-100 p-4 rounded-lg shadow-md">
                        <h3 class="text-lg font-semibold">Reports</h3>
                        <p class="text-gray-600">Generate and view system reports.</p>
                    </div>
                </div>
            </div>

            <!-- Faculty Information Section -->
            <div class="container mx-auto p-6 bg-white rounded-lg shadow-md">
                <div class="flex flex-col lg:flex-row justify-between">
                    <div class="w-full lg:w-2/3 p-4">
                        <h2 class="text-3xl font-bold mb-6">Faculty Information</h2>
                        <form action="submission.php" method="post" enctype="multipart/form-data">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Input fields for faculty information -->
                                <div>
                                    <label class="block text-gray-700 mb-1">Faculty Branch</label>
                                    <input type="text" name="faculty_branch" class="w-full p-2 border border-gray-300 rounded" placeholder="Enter faculty branch" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-1">Faculty Name</label>
                                    <input type="text" name="faculty_name_display" class="w-full p-2 border border-gray-300 rounded" value="<?php echo htmlspecialchars($userInfo['username']); ?>" readonly>
                                    <input type="hidden" name="faculty_name" value="<?php echo htmlspecialchars($userInfo['username']); ?>">
                                </div>

                                <div>
                                    <label class="block text-gray-700 mb-1">Paper Title</label>
                                    <input type="text" name="faculty_data" class="w-full p-2 border border-gray-300 rounded" placeholder="Enter Your Title" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-1">Year</label>
                                    <input type="datetime-local" name="faculty_datetime" class="w-full p-2 border border-gray-300 rounded" required>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 mb-1">Upload PDF</label>
                                    <button type="button" onclick="triggerFileInput()" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                                        Browse Files
                                    </button>
                                    <input type="file" id="file-input" name="pdf_upload" class="hidden" accept=".pdf" onchange="handleFileSelect(event)" required>
                                </div>
                            </div>
                            <!-- Submit button -->
                            <div class="flex justify-center mt-4">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        // Toggle the visibility of the dropdown menu
        function toggleDropdown() {
            const dropdown = document.getElementById('profile-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Logout function to redirect to the login page
        function logout() {
            window.location.href = 'logout.php';
        }

        // Close the dropdown if clicked outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profile-dropdown');
            const profileButton = event.target.closest('.relative');
            if (!profileButton) {
                dropdown.classList.add('hidden');
            }
        });

        function triggerFileInput() {
            document.getElementById('file-input').click();
        }

        function handleFileSelect(event) {
            const files = event.target.files;
            if (files.length > 0) {
                alert(`Selected file: ${files[0].name}`);
            }
        }
    </script>
</body>
</html>
