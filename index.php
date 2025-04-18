<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'music_school_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music School Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-gradient: linear-gradient(to right, #003366, #004080, #0059b3, #0073e6);
            --accent-color: #1a75ff;
            --purple-gradient: linear-gradient(to right, #6B46C1, #9F7AEA);
            --pink-gradient: linear-gradient(to right, #D53F8C, #F687B3);
            --text-color: #333;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e4f5 100%);
            overflow-x: hidden;
        }
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');
        .hero-bg {
            background: url('https://source.unsplash.com/1600x900/?music-school') no-repeat center center/cover;
            position: relative;
            z-index: 1;
        }
        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            opacity: 0.7;
            z-index: -1;
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        .dashboard-bg {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
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
                <li><a href="dashboard.php#about" class="text-white hover:text-pink-200 transition duration-300 pink-hover">About</a></li>
                <li><a href="index.php?logout=true" class="text-white hover:text-red-200 transition duration-300 red-hover">Logout</a></li>
            </ul>
        </div>
    </nav>

    <section class="min-h-screen hero-bg flex items-center justify-center text-white relative overflow-hidden">
        <div class="text-center px-6 animate__animated animate__fadeInUp">
            <h1 class="text-5xl md:text-6xl font-extrabold mb-4 leading-tight text-blue-100">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p class="text-xl md:text-2xl mb-6 max-w-2xl mx-auto text-blue-200">Elevate your music school with our all-in-one management solution.</p>
            <a href="#features" class="bg-gradient-to-r from-blue-600 to-purple-400 text-white font-semibold py-3 px-8 rounded-full shadow-lg hover:from-pink-600 hover:to-blue-400 transition duration-300">Discover Now</a>
        </div>
        <div class="absolute inset-0 bg-black opacity-20"></div>
    </section>

    <section id="features" class="py-20 px-4 bg-gradient-to-r from-blue-100 to-purple-100">
        <h2 class="text-4xl font-bold text-center mb-12 text-blue-800 animate__animated animate__fadeIn">Our Key Features</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 max-w-6xl mx-auto">
            <div class="bg-white p-8 rounded-xl card-hover shadow-md border-2 border-blue-300">
                <div class="text-4xl text-blue-600 mb-4"><i class='bx bx-user-plus'></i></div>
                <h3 class="text-2xl font-semibold text-blue-800 mb-2">Student Enrollment</h3>
                <p class="text-gray-600">Streamline student registrations with intuitive forms.</p>
                <a href="enrollment.php" class="mt-4 inline-block text-blue-500 hover:text-purple-500 transition duration-300">Explore</a>
            </div>
            <div class="bg-white p-8 rounded-xl card-hover shadow-md border-2 border-purple-300">
                <div class="text-4xl text-purple-600 mb-4"><i class='bx bx-calendar'></i></div>
                <h3 class="text-2xl font-semibold text-purple-800 mb-2">Lesson Scheduling</h3>
                <p class="text-gray-600">Effortlessly plan and manage lesson timetables.</p>
                <a href="scheduling.php" class="mt-4 inline-block text-purple-500 hover:text-pink-500 transition duration-300">Explore</a>
            </div>
            <div class="bg-white p-8 rounded-xl card-hover shadow-md border-2 border-pink-300">
                <div class="text-4xl text-pink-600 mb-4"><i class='bx bx-chalkboard'></i></div>
                <h3 class="text-2xl font-semibold text-pink-800 mb-2">Teacher Assignments</h3>
                <p class="text-gray-600">Assign teachers with precision and flexibility.</p>
                <a href="assignment.php" class="mt-4 inline-block text-pink-500 hover:text-blue-500 transition duration-300">Explore</a>
            </div>
            <div class="bg-white p-8 rounded-xl card-hover shadow-md border-2 border-purple-300">
                <div class="text-4xl text-purple-600 mb-4"><i class='bx bx-music'></i></div>
                <h3 class="text-2xl font-semibold text-purple-800 mb-2">Practice Tracking</h3>
                <p class="text-gray-600">Monitor student practice with detailed insights.</p>
                <a href="practice.php" class="mt-4 inline-block text-purple-500 hover:text-pink-500 transition duration-300">Explore</a>
            </div>
            <div class="bg-white p-8 rounded-xl card-hover shadow-md border-2 border-pink-300">
                <div class="text-4xl text-pink-600 mb-4"><i class='bx bx-microphone'></i></div>
                <h3 class="text-2xl font-semibold text-pink-800 mb-2">Recital Planning</h3>
                <p class="text-gray-600">Organize recitals with ease and elegance.</p>
                <a href="recital.php" class="mt-4 inline-block text-pink-500 hover:text-blue-500 transition duration-300">Explore</a>
            </div>
            <div class="bg-white p-8 rounded-xl card-hover shadow-md border-2 border-blue-300">
                <div class="text-4xl text-blue-600 mb-4"><i class='bx bx-message-square-detail'></i></div>
                <h3 class="text-2xl font-semibold text-blue-800 mb-2">Collaboration Tool</h3>
                <p class="text-gray-600">Connect with teachers and parents seamlessly.</p>
                <a href="collaboration.php" class="mt-4 inline-block text-blue-500 hover:text-purple-500 transition duration-300">Explore</a>
            </div>
        </div>
    </section>

    <section id="dashboard" class="py-20 px-4 bg-gradient-to-r from-blue-100 to-pink-100">
        <h2 class="text-4xl font-bold text-center mb-12 text-blue-800 animate__animated animate__fadeIn">Personalized Dashboard</h2>
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-10">
            <div class="dashboard-bg p-8 rounded-xl shadow-2xl border-2 border-blue-300">
                <h3 class="text-2xl font-semibold text-blue-600 mb-4">Real-Time Overview</h3>
                <p class="text-gray-600 mb-4">Track student progress, attendance, and schedule updates in real-time.</p>
                <a href="dashboard.php" class="bg-gradient-to-r from-blue-600 to-purple-400 text-white font-semibold py-2 px-6 rounded-full hover:from-pink-600 hover:to-blue-400 transition duration-300">View Dashboard</a>
            </div>
            <div class="dashboard-bg p-8 rounded-xl shadow-2xl border-2 border-pink-300">
                <h3 class="text-2xl font-semibold text-pink-600 mb-4">Collaboration Tools</h3>
                <p class="text-gray-600 mb-4">Work together with teachers and parents to enhance communication.</p>
                <a href="collaboration.php" class="bg-gradient-to-r from-pink-600 to-blue-400 text-white font-semibold py-2 px-6 rounded-full hover:from-purple-600 hover:to-pink-400 transition duration-300">Start Collaboration</a>
            </div>
        </div>
    </section>

    <section class="py-20 bg-gradient-to-r from-blue-900 to-purple-500 text-white text-center">
        <h2 class="text-4xl font-bold mb-6 text-pink-200 animate__animated animate__fadeIn">Ready to Transform Your School?</h2>
        <p class="text-xl mb-8 max-w-2xl mx-auto text-blue-100">Join thousands of music schools using our platform to manage and grow.</p>
        <a href="#" class="bg-gradient-to-r from-purple-600 to-pink-400 text-white font-semibold py-3 px-8 rounded-full shadow-lg hover:from-blue-600 hover:to-purple-400 transition duration-300">Get Started</a>
    </section>

    <footer class="bg-gradient-to-r from-blue-800 to-pink-800 text-white py-8">
        <div class="container mx-auto text-center">
            <p class="mb-2 text-blue-100">© 2025 Music School Management. All rights reserved.</p>
            <a href="#" class="text-purple-200 hover:text-blue-300 transition duration-300">Privacy Policy</a> |
            <a href="#" class="text-pink-200 hover:text-purple-300 transition duration-300">Terms of Service</a>
        </div>
    </footer>
</body>
</html>