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
    logDebug("Database connection successful (dashboard.php)");
} catch (PDOException $e) {
    logDebug("Connection failed (dashboard.php): " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    logDebug("Session user_id missing or invalid (dashboard.php)");
    header("Location: login.php");
    exit;
}
logDebug("User ID (dashboard.php): " . $_SESSION['user_id']);
$user_id = $_SESSION['user_id'];

// Handle feedback submission
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $feedback_text = trim($_POST['feedback_text'] ?? '');
    if (empty($feedback_text)) {
        $errors[] = "Feedback cannot be empty.";
        logDebug("Validation failed: Empty feedback (dashboard.php)");
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO feedback (user_id, feedback_text) VALUES (?, ?)");
            $stmt->execute([$user_id, $feedback_text]);
            $success = "Feedback submitted successfully!";
            logDebug("Feedback inserted successfully for user_id: $user_id (dashboard.php)");
        } catch (PDOException $e) {
            $errors[] = "Error submitting feedback: " . $e->getMessage();
            logDebug("Feedback insert failed (dashboard.php): " . $e->getMessage());
        }
    }
}

// Fetch dashboard data
// Total students
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    logDebug("Fetched total students for user_id $user_id: $total_students (dashboard.php)");
} catch (PDOException $e) {
    $errors[] = "Error fetching student count: " . $e->getMessage();
    logDebug("Student count fetch failed (dashboard.php): " . $e->getMessage());
    $total_students = 0;
}

// Upcoming lessons (next 7 days)
try {
    $stmt = $pdo->prepare("SELECT student_name, lesson_date, lesson_time FROM lesson_schedules WHERE user_id = ? AND lesson_date >= CURDATE() AND lesson_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY lesson_date, lesson_time LIMIT 5");
    $stmt->execute([$user_id]);
    $upcoming_lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Fetched upcoming lessons for user_id $user_id: " . json_encode($upcoming_lessons) . " (dashboard.php)");
} catch (PDOException $e) {
    $errors[] = "Error fetching lessons: " . $e->getMessage();
    logDebug("Lessons fetch failed (dashboard.php): " . $e->getMessage());
    $upcoming_lessons = [];
}

// Recent practice logs (last 7 days)
try {
    $stmt = $pdo->prepare("SELECT student_name, practice_hours, created_at FROM practice_logs WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_practice = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Fetched recent practice logs for user_id $user_id: " . json_encode($recent_practice) . " (dashboard.php)");
} catch (PDOException $e) {
    $errors[] = "Error fetching practice logs: " . $e->getMessage();
    logDebug("Practice logs fetch failed (dashboard.php): " . $e->getMessage());
    $recent_practice = [];
}

// Planned recitals
try {
    $stmt = $pdo->prepare("SELECT event_name, recital_date FROM recitals WHERE user_id = ? AND recital_date >= CURDATE() ORDER BY recital_date LIMIT 5");
    $stmt->execute([$user_id]);
    $planned_recitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Fetched planned recitals for user_id $user_id: " . json_encode($planned_recitals) . " (dashboard.php)");
} catch (PDOException $e) {
    $errors[] = "Error fetching recitals: " . $e->getMessage();
    logDebug("Recitals fetch failed (dashboard.php): " . $e->getMessage());
    $planned_recitals = [];
}

// Teacher assignments
try {
    $stmt = $pdo->prepare("SELECT teacher_name, class_name FROM assignments WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $teacher_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Fetched teacher assignments for user_id $user_id: " . json_encode($teacher_assignments) . " (dashboard.php)");
} catch (PDOException $e) {
    $errors[] = "Error fetching assignments: " . $e->getMessage();
    logDebug("Assignments fetch failed (dashboard.php): " . $e->getMessage());
    $teacher_assignments = [];
}

// Instrument distribution for pie chart
try {
    $stmt = $pdo->prepare("SELECT instrument, COUNT(*) as count FROM students WHERE user_id = ? GROUP BY instrument");
    $stmt->execute([$user_id]);
    $instrument_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Fetched instrument data for user_id $user_id: " . json_encode($instrument_data) . " (dashboard.php)");
} catch (PDOException $e) {
    $errors[] = "Error fetching instrument data: " . $e->getMessage();
    logDebug("Instrument data fetch failed (dashboard.php): " . $e->getMessage());
    $instrument_data = [];
}

// Practice hours for bar chart (last 7 days)
try {
    $stmt = $pdo->prepare("SELECT DATE(created_at) as practice_date, SUM(practice_hours) as total_hours FROM practice_logs WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY practice_date");
    $stmt->execute([$user_id]);
    $practice_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logDebug("Fetched practice data for user_id $user_id: " . json_encode($practice_data) . " (dashboard.php)");
} catch (PDOException $e) {
    $errors[] = "Error fetching practice data: " . $e->getMessage();
    logDebug("Practice data fetch failed (dashboard.php): " . $e->getMessage());
    $practice_data = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li><a href="dashboard.php" class="text-white hover:text-blue-200 transition duration-300 blue-hover">Dashboard</a></li>
                <!-- <li><a href="#feedback" class="text-white hover:text-purple-200 transition duration-300 purple-hover">Feedback</a></li> -->
                <!-- <li><a href="#about" class="text-white hover:text-pink-200 transition duration-300 pink-hover">About</a></li> -->
                <li><a href="index.php?logout=true" class="text-white hover:text-red-200 transition duration-300 red-hover">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Content -->
    <section class="min-h-screen pt-20 px-6">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-4xl font-extrabold text-blue-600 mb-8 text-center animate__animated animate__fadeIn">Dashboard</h2>
            <p class="text-gray-600 mb-12 text-center">Gain insights into your music school operations with real-time data.</p>

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

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white p-6 rounded-xl shadow-md card-hover border-2 border-blue-300 animate__animated animate__fadeInUp">
                    <div class="text-4xl text-blue-600 mb-4"><i class='bx bx-user-plus'></i></div>
                    <h3 class="text-xl font-semibold text-blue-800">Total Students</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $total_students; ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-md card-hover border-2 border-purple-300 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                    <div class="text-4xl text-purple-600 mb-4"><i class='bx bx-calendar'></i></div>
                    <h3 class="text-xl font-semibold text-purple-800">Upcoming Lessons</h3>
                    <p class="text-3xl font-bold text-purple-600"><?php echo count($upcoming_lessons); ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-md card-hover border-2 border-pink-300 animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                    <div class="text-4xl text-pink-600 mb-4"><i class='bx bx-microphone'></i></div>
                    <h3 class="text-xl font-semibold text-pink-800">Planned Recitals</h3>
                    <p class="text-3xl font-bold text-pink-600"><?php echo count($planned_recitals); ?></p>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
                <div class="bg-white p-6 rounded-xl shadow-md border-2 border-blue-300">
                    <h3 class="text-xl font-semibold text-blue-800 mb-4">Instrument Distribution</h3>
                    <canvas id="instrumentChart" height="200"></canvas>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-md border-2 border-purple-300">
                    <h3 class="text-xl font-semibold text-purple-800 mb-4">Practice Hours (Last 7 Days)</h3>
                    <canvas id="practiceChart" height="200"></canvas>
                </div>
            </div>

            <!-- Data Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
                <!-- Upcoming Lessons -->
                <div class="bg-white p-6 rounded-xl shadow-md border-2 border-purple-300">
                    <h3 class="text-xl font-semibold text-purple-800 mb-4">Upcoming Lessons</h3>
                    <?php if (empty($upcoming_lessons)): ?>
                        <p class="text-gray-600">No upcoming lessons.</p>
                    <?php else: ?>
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 text-purple-600">Student</th>
                                    <th class="py-2 text-purple-600">Date</th>
                                    <th class="py-2 text-purple-600">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_lessons as $lesson): ?>
                                    <tr class="border-b">
                                        <td class="py-2"><?php echo htmlspecialchars($lesson['student_name']); ?></td>
                                        <td class="py-2"><?php echo htmlspecialchars($lesson['lesson_date']); ?></td>
                                        <td class="py-2"><?php echo htmlspecialchars($lesson['lesson_time']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Recent Practice Logs -->
                <div class="bg-white p-6 rounded-xl shadow-md border-2 border-pink-300">
                    <h3 class="text-xl font-semibold text-pink-800 mb-4">Recent Practice Logs</h3>
                    <?php if (empty($recent_practice)): ?>
                        <p class="text-gray-600">No recent practice logs.</p>
                    <?php else: ?>
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 text-pink-600">Student</th>
                                    <th class="py-2 text-pink-600">Hours</th>
                                    <th class="py-2 text-pink-600">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_practice as $log): ?>
                                    <tr class="border-b">
                                        <td class="py-2"><?php echo htmlspecialchars($log['student_name']); ?></td>
                                        <td class="py-2"><?php echo htmlspecialchars($log['practice_hours']); ?></td>
                                        <td class="py-2"><?php echo date('Y-m-d', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Planned Recitals -->
                <div class="bg-white p-6 rounded-xl shadow-md border-2 border-blue-300">
                    <h3 class="text-xl font-semibold text-blue-800 mb-4">Planned Recitals</h3>
                    <?php if (empty($planned_recitals)): ?>
                        <p class="text-gray-600">No planned recitals.</p>
                    <?php else: ?>
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 text-blue-600">Event Name</th>
                                    <th class="py-2 text-blue-600">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($planned_recitals as $recital): ?>
                                    <tr class="border-b">
                                        <td class="py-2"><?php echo htmlspecialchars($recital['event_name']); ?></td>
                                        <td class="py-2"><?php echo htmlspecialchars($recital['recital_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Teacher Assignments -->
                <div class="bg-white p-6 rounded-xl shadow-md border-2 border-pink-300">
                    <h3 class="text-xl font-semibold text-pink-800 mb-4">Teacher Assignments</h3>
                    <?php if (empty($teacher_assignments)): ?>
                        <p class="text-gray-600">No teacher assignments.</p>
                    <?php else: ?>
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 text-pink-600">Teacher</th>
                                    <th class="py-2 text-pink-600">Class</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teacher_assignments as $assignment): ?>
                                    <tr class="border-b">
                                        <td class="py-2"><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                        <td class="py-2"><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Feedback Section -->
            <section id="feedback" class="mb-12">
                <div class="bg-white p-6 rounded-xl shadow-md border-2 border-purple-300 card-hover animate__animated animate__fadeInUp">
                    <h3 class="text-xl font-semibold text-purple-800 mb-4">Share Your Feedback</h3>
                    <p class="text-gray-600 mb-6">We value your input to improve our system. Let us know your thoughts!</p>
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-gray-700 font-medium">Your Feedback</label>
                            <textarea name="feedback_text" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition duration-300" rows="5" placeholder="Enter your feedback here" required></textarea>
                        </div>
                        <button type="submit" name="submit_feedback" class="w-full bg-gradient-to-r from-purple-600 to-pink-400 hover:from-blue-600 hover:to-blue-400 text-white p-3 rounded-lg transition duration-300 shadow-md">Submit Feedback</button>
                    </form>
                </div>
            </section>

            <!-- About Section -->
            <section id="about" class="mb-12">
                <div class="bg-white p-6 rounded-xl shadow-md border-2 border-blue-300 card-hover animate__animated animate__fadeInUp">
                    <h3 class="text-xl font-semibold text-blue-800 mb-4">About Music School Manager</h3>
                    <p class="text-gray-600 mb-4">Music School Manager is an all-in-one platform designed to streamline the operations of music schools. From student enrollment to recital planning, our system empowers administrators, teachers, and parents with intuitive tools to manage schedules, track progress, and foster musical growth.</p>
                    <p class="text-gray-600 mb-4">Built with a passion for music education, our platform combines modern technology with user-friendly design to create a seamless experience. We are committed to continuous improvement based on your feedback.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-blue-500 hover:text-purple-500 transition duration-300">Learn More</a>
                        <a href="#feedback" class="text-purple-500 hover:text-pink-500 transition duration-300">Share Feedback</a>
                    </div>
                </div>
            </section>
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

        // Chart.js for Instrument Distribution (Pie Chart)
        const instrumentCtx = document.getElementById('instrumentChart').getContext('2d');
        const instrumentData = <?php echo json_encode($instrument_data); ?>;
        new Chart(instrumentCtx, {
            type: 'pie',
            data: {
                labels: instrumentData.map(item => item.instrument || 'Unknown'),
                datasets: [{
                    data: instrumentData.map(item => item.count),
                    backgroundColor: ['#003366', '#6B46C1', '#D53F8C', '#0059b3', '#F687B3'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Poppins', size: 14 },
                            color: '#333'
                        }
                    }
                }
            }
        });

        // Chart.js for Practice Hours (Bar Chart)
        const practiceCtx = document.getElementById('practiceChart').getContext('2d');
        const practiceData = <?php echo json_encode($practice_data); ?>;
        new Chart(practiceCtx, {
            type: 'bar',
            data: {
                labels: practiceData.map(item => item.practice_date),
                datasets: [{
                    label: 'Practice Hours',
                    data: practiceData.map(item => item.total_hours),
                    backgroundColor: 'rgba(107, 70, 193, 0.7)',
                    borderColor: '#6B46C1',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Hours',
                            font: { family: 'Poppins', size: 14 },
                            color: '#333'
                        },
                        ticks: {
                            font: { family: 'Poppins', size: 12 },
                            color: '#333'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date',
                            font: { family: 'Poppins', size: 14 },
                            color: '#333'
                        },
                        ticks: {
                            font: { family: 'Poppins', size: 12 },
                            color: '#333'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>