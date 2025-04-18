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
    logDebug("Database connection successful (practice.php)");
} catch (PDOException $e) {
    logDebug("Connection failed (practice.php): " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    logDebug("Session user_id missing or invalid (practice.php)");
    header("Location: login.php");
    exit;
}
logDebug("User ID (practice.php): " . $_SESSION['user_id']);

$errors = [];
$success = '';
$students = [];

// Fetch enrolled student for the user
try {
    $stmt = $pdo->prepare("SELECT id, student_name FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Fetched students for user_id {$_SESSION['user_id']}: " . json_encode($students) . " (practice.php)");
} catch (PDOException $e) {
    $errors[] = "Error fetching students: " . $e->getMessage();
    logDebug("Student fetch failed (practice.php): " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track'])) {
    logDebug("Form submitted (practice.php): " . json_encode($_POST));
    
    $student_id = trim($_POST['student_id'] ?? '');
    $practice_hours = trim($_POST['hours'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (empty($student_id) || empty($practice_hours)) {
        $errors[] = "Student and practice hours are required.";
        logDebug("Validation failed: Missing required fields (practice.php)");
    } elseif (!is_numeric($practice_hours) || $practice_hours <= 0) {
        $errors[] = "Practice hours must be a positive number.";
        logDebug("Validation failed: Invalid practice hours (practice.php)");
    } else {
        // Validate student_id
        $student = null;
        foreach ($students as $s) {
            if ($s['id'] == $student_id) {
                $student = $s;
                break;
            }
        }
        
        if (!$student) {
            $errors[] = "Selected student is not enrolled.";
            logDebug("Validation failed: Invalid student_id $student_id for user_id $user_id (practice.php)");
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO practice_logs (student_id, user_id, student_name, practice_hours) VALUES (?, ?, ?, ?)");
                $stmt->execute([$student_id, $user_id, $student['student_name'], $practice_hours]);
                $success = "Practice hours tracked successfully!";
                logDebug("Practice log inserted successfully for user_id: $user_id, student_id: $student_id (practice.php)");
            } catch (PDOException $e) {
                $errors[] = "Error tracking practice: " . $e->getMessage();
                logDebug("Insert failed (practice.php): " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice Tracking</title>
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
                <li><a href="#dashboard" class="text-white hover:text-blue-200 transition duration-300 blue-hover">Dashboard</a></li>
                <li><a href="index.php?logout=true" class="text-white hover:text-red-200 transition duration-300 red-hover">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Content -->
    <section class="min-h-screen pt-20 px-6 flex items-center justify-center">
        <div class="max-w-xl w-full bg-white p-10 rounded-2xl shadow-2xl animate__animated animate__fadeInUp">
            <h2 class="text-4xl font-extrabold text-blue-600 mb-6 text-center">Practice Tracking</h2>
            <p class="text-gray-600 mb-8 text-center">Track student practice hours with precision and elegance.</p>
            <?php if (!empty($errors)): ?>
                <div class="mb-4 text-center text-red-600 bg-red-100 p-3 rounded-lg">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-4 text-center text-green-600 bg-green-100 p-3 rounded-lg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form id="practiceForm" method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 font-medium">Student</label>
                    <select name="student_id" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" required>
                        <?php if (empty($students)): ?>
                            <option value="">No students enrolled</option>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['student_name']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-medium">Practice Hours</label>
                    <input type="number" name="hours" step="0.1" min="0.1" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" placeholder="Enter hours (e.g., 1.5)" required>
                </div>
                <button type="submit" name="track" class="w-full bg-gradient-to-r from-purple-600 to-pink-400 hover:from-blue-600 hover:to-blue-400 text-white p-3 rounded-lg transition duration-300 shadow-md">Track Practice</button>
            </form>
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

    <script>
        // XLSX Processing (included but not integrated with PHP)
        var gk_isXlsx = false;
        var gk_xlsxFileLookup = {};
        var gk_fileData = {};
        function filledCell(cell) {
            return cell !== '' && cell != null;
        }
        function loadFileData(filename) {
            if (gk_isXlsx && gk_xlsxFileLookup[filename]) {
                try {
                    var workbook = XLSX.read(gk_fileData[filename], { type: 'base64' });
                    var firstSheetName = workbook.SheetNames[0];
                    var worksheet = workbook.Sheets[firstSheetName];
                    var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                    var filteredData = jsonData.filter(row => row.some(filledCell));
                    var headerRowIndex = filteredData.findIndex((row, index) =>
                        row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                    );
                    if (headerRowIndex === -1 || headerRowIndex > 25) {
                        headerRowIndex = 0;
                    }
                    var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex));
                    csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
                    return csv;
                } catch (e) {
                    console.error(e);
                    return "";
                }
            }
            return gk_fileData[filename] || "";
        }
    </script>
</body>
</html>