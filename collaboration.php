<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug logging function
function logDebug($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

// Database connection
$host = 'localhost';
$dbname = 'music_school_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logDebug("Database connection successful (collaboration.php)");
} catch (PDOException $e) {
    logDebug("Connection failed (collaboration.php): " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    logDebug("Session user_id missing or invalid (collaboration.php)");
    header("Location: login.php");
    exit;
}
logDebug("User ID (collaboration.php): " . $_SESSION['user_id']);
$user_id = $_SESSION['user_id'];

// Handle message submission
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_type = trim($_POST['recipient_type'] ?? '');
    $recipient_name = trim($_POST['recipient_name'] ?? '');
    $message_text = trim($_POST['message_text'] ?? '');
    if (empty($recipient_type) || empty($recipient_name) || empty($message_text)) {
        $errors[] = "Recipient type, name, and message are required.";
        logDebug("Validation failed: Missing collaboration fields (collaboration.php)");
    } elseif (!in_array($recipient_type, ['teacher', 'parent'])) {
        $errors[] = "Invalid recipient type.";
        logDebug("Validation failed: Invalid recipient type (collaboration.php)");
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO collaboration_messages (user_id, recipient_type, recipient_name, message_text) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $recipient_type, $recipient_name, $message_text]);
            $success = "Message sent successfully!";
            logDebug("Collaboration message inserted successfully for user_id: $user_id (collaboration.php)");
        } catch (PDOException $e) {
            $errors[] = "Error sending message: " . $e->getMessage();
            logDebug("Message insert failed (collaboration.php): " . $e->getMessage());
        }
    }
}

// Fetch recent messages (last 5)
try {
    $stmt = $pdo->prepare("SELECT recipient_type, recipient_name, message_text, created_at FROM collaboration_messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Fetched recent messages for user_id $user_id: " . json_encode($recent_messages) . " (collaboration.php)");
} catch (PDOException $e) {
    $errors[] = "Error fetching messages: " . $e->getMessage();
    logDebug("Messages fetch failed (collaboration.php): " . $e->getMessage());
    $recent_messages = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collaboration Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-gradient: linear-gradient(to right, #003366, #004080, #0059b3, #0073e6);
            --accent-color: #1a75ff;
            --purple-gradient: linear-gradient(to right, #6B46C1, #9F7AEA);
            --pink-gradient: linear-gradient(to right, #D53F8C, #F687B3);
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e4f5 100%);
        }
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');
        .purple-hover:hover {
            background: var(--purple-gradient);
            color: white;
        }
        .pink-hover:hover {
            background: var(--pink-gradient);
            color: white;
        }
        .blue-hover:hover {
            background: var(--primary-gradient);
            color: white;
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="text-gray-800">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-blue-900 to-blue-500 p-4 shadow-lg fixed w-full z-50">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="text-white text-3xl font-bold animate__animated animate__fadeInDown">Music School Manager</a>
            <ul class="flex space-x-6">
                <li><a href="enrollment.php" class="text-white hover:text-blue-200 transition duration-300 blue-hover">Student Enrollment</a></li>
                <li><a href="scheduling.php" class="text-white hover:text-purple-200 transition duration-300 purple-hover">Lesson Scheduling</a></li>
                <li><a href="assignment.php" class="text-white hover:text-pink-200 transition duration-300 pink-hover">Teacher Assignments</a></li>
                <li><a href="practice.php" class="text-white hover:text-purple-200 transition duration-300 purple-hover">Practice Tracking</a></li>
                <li><a href="recital.php" class="text-white hover:text-pink-200 transition duration-300 pink-hover">Recital Planning</a></li>
                <li><a href="collaboration.php" class="text-white hover:text-blue-200 transition duration-300 blue-hover">Collaboration</a></li>
                <li><a href="dashboard.php" class="text-white hover:text-blue-200 transition duration-300 blue-hover">Dashboard</a></li>
                <!-- <li><a href="dashboard.php#feedback" class="text-white hover:text-purple-200 transition duration-300 purple-hover">Feedback</a></li> -->
                <!-- <li><a href="dashboard.php#about" class="text-white hover:text-pink-200 transition duration-300 pink-hover">About</a></li> -->
                <li><a href="index.php?logout=true" class="text-white hover:text-red-200 transition duration-300 red-hover">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Content -->
    <section class="min-h-screen pt-20 px-6 flex items-center justify-center">
        <div class="max-w-4xl w-full">
            <h2 class="text-4xl font-extrabold text-blue-600 mb-8 text-center animate__animated animate__fadeIn">Collaboration Tool</h2>
            <p class="text-gray-600 mb-12 text-center">Connect with teachers and parents to enhance communication and coordination.</p>

            <?php if (!empty($errors)): ?>
                <div class="mb-8 text-center text-red-600 bg-red-100 p-4 rounded-lg">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-8 text-center text-green-600 bg-green-100 p-4 rounded-lg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Message Form -->
            <div class="bg-white p-8 rounded-xl shadow-md card-hover border-2 border-blue-300 mb-12 animate__animated animate__fadeInUp">
                <h3 class="text-xl font-semibold text-blue-800 mb-4">Send a Message</h3>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 font-medium">Recipient Type</label>
                        <select name="recipient_type" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" required>
                            <option value="teacher">Teacher</option>
                            <option value="parent">Parent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium">Recipient Name</label>
                        <input type="text" name="recipient_name" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" placeholder="Enter recipient name" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium">Message</label>
                        <textarea name="message_text" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" rows="5" placeholder="Enter your message" required></textarea>
                    </div>
                    <button type="submit" name="send_message" class="w-full bg-gradient-to-r from-blue-600 to-purple-400 hover:from-pink-600 hover:to-blue-400 text-white p-3 rounded-lg transition duration-300 shadow-md">Send Message</button>
                </form>
            </div>

            <!-- Recent Messages -->
            <div class="bg-white p-8 rounded-xl shadow-md card-hover border-2 border-purple-300 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <h3 class="text-xl font-semibold text-purple-800 mb-4">Recent Messages</h3>
                <?php if (empty($recent_messages)): ?>
                    <p class="text-gray-600">No messages sent yet.</p>
                <?php else: ?>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2 text-purple-600">Recipient Type</th>
                                <th class="py-2 text-purple-600">Recipient Name</th>
                                <th class="py-2 text-purple-600">Message</th>
                                <th class="py-2 text-purple-600">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_messages as $message): ?>
                                <tr class="border-b">
                                    <td class="py-2"><?php echo htmlspecialchars(ucfirst($message['recipient_type'])); ?></td>
                                    <td class="py-2"><?php echo htmlspecialchars($message['recipient_name']); ?></td>
                                    <td class="py-2"><?php echo htmlspecialchars($message['message_text']); ?></td>
                                    <td class="py-2"><?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-blue-800 to-pink-800 text-white py-8">
        <div class="container mx-auto text-center">
            <p class="mb-2 text-blue-100">© 2025 Music School Management. All rights reserved.</p>
            <a href="#" class="text-purple-200 hover:text-blue-300 transition duration-300">Privacy Policy</a> |
            <a href="#" class="text-pink-200 hover:text-purple-300 transition duration-300">Terms of Service</a>
        </div>
    </footer>
</body>
</html>