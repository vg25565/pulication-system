<?php
session_start();
require 'conn.php'; // Database connection
require 'send_email.php'; // Include email function
ob_start(); // Start output buffering

// Create a database connection
$conn = connectDatabase(); // Ensure $conn is initialized

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch the admin's name from the session
$adminName = $_SESSION['user_name'];

// Fetch PDFs for review
$query = "
    SELECT u.id AS upload_id, u.file_name, f.username AS faculty_name, f.email AS faculty_email, u.upload_date
    FROM uploads u
    JOIN users f ON u.user_id = f.id
    WHERE NOT EXISTS (SELECT 1 FROM papers p WHERE u.id = p.upload_id)";

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$papers = $result->fetch_all(MYSQLI_ASSOC);

// Fetch grievance records
$grievanceQuery = "
    SELECT g.id, g.grievance_text, g.status, f.username AS faculty_name, g.created_at
    FROM grievances g
    JOIN users f ON g.faculty_id = f.id";
$grievanceResult = $conn->query($grievanceQuery);

if (!$grievanceResult) {
    die("Grievance query failed: " . $conn->error);
}

$grievances = $grievanceResult->fetch_all(MYSQLI_ASSOC);

// Handle approval or rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uploadId = $_POST['upload_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $title = $_POST['title'] ?? null;
    $facultyEmail = $_POST['faculty_email'] ?? null;

    if ($uploadId && $action && $title && $facultyEmail) {
        $status = ($action == 'approve') ? 'approved' : 'rejected';

        // Insert record into the papers table
        $stmt = $conn->prepare("INSERT INTO papers (upload_id, title, status) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iss", $uploadId, $title, $status);
            $stmt->execute();
            $stmt->close();
        } else {
            die("Statement preparation failed: " . $conn->error);
        }

        // Update the uploads table with the new status
        $updateQuery = "UPDATE uploads SET status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        if ($updateStmt) {
            $updateStmt->bind_param("si", $status, $uploadId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            die("Failed to update uploads table: " . $conn->error);
        }

        // Send email notification to the faculty
        $subject = "Your PDF Submission has been " . ucfirst($status);
        $message = "Dear Faculty,\n\nYour PDF titled '" . htmlspecialchars($title) . "' has been " . $status . ".\n\nThank you,\nAdmin";
        sendEmail($facultyEmail, $subject, $message); // Call the send_email function

        // Redirect back to the admin dashboard
        header("Location: admin.php");
        exit();
    } else {
        echo "Required fields are missing.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .transition-transform {
            transition: transform 0.3s ease-in-out;
        }
        .iframe-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            max-width: 100%;
            background: #000;
        }
        .iframe-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .hidden {
            display: none;
        }
        .translate-x-full {
            transform: translateX(100%);
        }
        .translate-x-0 {
            transform: translateX(0);
        }
        .ml-64 {
            margin-left: 16rem; /* Sidebar width */
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-indigo-900 text-white transform transition-transform -translate-x-full">
        <div class="flex items-center justify-between h-16 bg-indigo-900 px-4">
            <h1 class="text-2xl font-bold">Admin</h1>
            <button id="closeSidebar" class="text-white focus:outline-none hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Display Admin's Name and Role -->
        <div class="flex items-center space-x-4 p-4 hover:bg-indigo-400 hover:text-black">
            <img class="w-12 h-12 rounded-full" src="https://via.placeholder.com/150" alt="Avatar">
            <div>
                <span class="text-white font-semibold"><?php echo htmlspecialchars($adminName); ?></span>
                <p class="text-white text-sm">Admin</p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="mt-4">
            <a href="admin.php" class="block px-4 py-2 hover:bg-indigo-400 hover:text-black">Dashboard</a>
            <a href="users.php" class="block px-4 py-2 hover:bg-indigo-400 hover:text-black">Users</a>
            <a href="settings.php" class="block px-4 py-2 hover:bg-indigo-400 hover:text-black">Settings</a>
            <a href="logout.php" class="block px-4 py-2 hover:bg-indigo-400 hover:text-black">Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div id="content" class="flex flex-col transition-transform ml-64">
        <div class="flex items-center justify-between h-16 bg-indigo-900 shadow px-4">
            <button id="menuButton" class="text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
            <h1 class="text-xl font-bold text-white">Welcome, <?php echo htmlspecialchars($adminName); ?>!</h1>
            <div class="flex-grow max-w-sm lg:max-w-md mx-4">
                <input type="text" placeholder="Search..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-black focus:border-gray-300">
            </div>
        </div>

        <div class="p-4">
            <h2 class="text-2xl font-semibold text-gray-700">Faculty Paper Submissions</h2>
            <table class="w-full bg-white shadow-md rounded-lg border border-gray-200">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 text-left border-b border-gray-200">
                        <th class="py-3 px-4">Faculty Name</th>
                        <th class="py-3 px-4">PDF File Name</th>
                        <th class="py-3 px-4">Submission Date</th>
                        <th class="py-3 px-4">View PDF</th>
                        <th class="py-3 px-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($papers as $paper): ?>
                        <tr class="border-b border-gray-200">
                            <td class="py-2 px-4"><?php echo htmlspecialchars($paper['faculty_name']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($paper['file_name']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($paper['upload_date']); ?></td>
                            <td class="py-2 px-4">
                                <a href="#" onclick="showPdfModal('uploads/<?php echo htmlspecialchars($paper['file_name']); ?>')" class="text-blue-500 hover:underline">View PDF</a>
                            </td>
                            <td class="py-2 px-4">
                                <form action="admin.php" method="POST" class="flex space-x-2">
                                    <input type="hidden" name="upload_id" value="<?php echo htmlspecialchars($paper['upload_id']); ?>">
                                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($paper['file_name']); ?>">
                                    <input type="hidden" name="faculty_email" value="<?php echo htmlspecialchars($paper['faculty_email']); ?>">
                                    <button type="submit" name="action" value="approve" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Approve</button>
                                    <button type="submit" name="action" value="reject" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2 class="text-2xl font-semibold text-gray-700 mt-8">Grievance Records</h2>
            <table class="w-full bg-white shadow-md rounded-lg border border-gray-200 mt-4">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 text-left border-b border-gray-200">
                        <th class="py-3 px-4">Faculty Name</th>
                        <th class="py-3 px-4">Grievance</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grievances as $grievance): ?>
                        <tr class="border-b border-gray-200">
                            <td class="py-2 px-4"><?php echo htmlspecialchars($grievance['faculty_name']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($grievance['grievance_text']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($grievance['status']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($grievance['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PDF Modal -->
    <div id="pdfModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-75 z-50 hidden">
        <div class="bg-white p-4 rounded-lg shadow-lg max-w-4xl w-full relative">
            <button id="closePdfModal" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">&times;</button>
            <div class="iframe-container">
                <iframe id="pdfIframe" src="" frameborder="0"></iframe>
            </div>
        </div>
    </div>

    <script>
        function showPdfModal(pdfUrl) {
            document.getElementById('pdfIframe').src = pdfUrl;
            document.getElementById('pdfModal').classList.remove('hidden');
        }

        document.getElementById('closePdfModal').addEventListener('click', function() {
            document.getElementById('pdfModal').classList.add('hidden');
            document.getElementById('pdfIframe').src = ''; // Clear iframe source to stop PDF from loading
        });

        document.getElementById('menuButton').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
            document.getElementById('sidebar').classList.add('translate-x-0');
            document.getElementById('menuButton').classList.add('hidden');
            document.getElementById('closeSidebar').classList.remove('hidden');
            document.getElementById('content').classList.add('ml-64');
        });

        document.getElementById('closeSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('translate-x-0');
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('menuButton').classList.remove('hidden');
            document.getElementById('closeSidebar').classList.add('hidden');
            document.getElementById('content').classList.remove('ml-64');
        });
    </script>
</body>
</html>
