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

$errors = [];
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'login';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}

// Handle signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username or email already taken.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            try {
                $stmt->execute([$username, $email, $hashed_password]);
                $success = "Registration successful! Please sign in.";
                $action = 'login';
            } catch (PDOException $e) {
                $errors[] = "Registration failed: " . $e->getMessage();
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
    <title>Sign In / Sign Up</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-color: #0059b3;
            --secondary-color: #0073e6;
            --accent-color: #1a75ff;
            --black: #000000;
            --white: #ffffff;
            --gray: #efefef;
            --gray-2: #757575;
            --facebook-color: #4267B2;
            --google-color: #DB4437;
            --twitter-color: #1DA1F2;
            --insta-color: #E1306C;
        }

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');

        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100vh;
            overflow: hidden;
        }

        .container {
            position: relative;
            min-height: 100vh;
            overflow: hidden;
            transition: all 0.5s ease-in-out;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            height: 100vh;
        }

        .col {
            width: 50%;
            transition: transform 0.8s ease-in-out;
        }

        .align-items-center {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .form-wrapper {
            width: 100%;
            max-width: 28rem;
        }

        .form {
            padding: 1rem;
            background-color: var(--white);
            border-radius: 1.5rem;
            width: 100%;
            box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
            transform: scale(0);
            transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out;
            opacity: 0;
        }

        .form.active {
            transform: scale(1);
            opacity: 1;
        }

        .input-group {
            position: relative;
            width: 100%;
            margin: 1rem 0;
        }

        .input-group i {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            font-size: 1.4rem;
            color: var(--gray-2);
        }

        .input-group input {
            width: 100%;
            padding: 1rem 3rem;
            font-size: 1rem;
            background-color: var(--gray);
            border-radius: .5rem;
            border: 0.125rem solid var(--white);
            outline: none;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus {
            border: 0.125rem solid var(--accent-color);
        }

        .form button {
            cursor: pointer;
            width: 100%;
            padding: .6rem 0;
            border-radius: .5rem;
            border: none;
            background: linear-gradient(to right, #003366, #004080, #0059b3, #0073e6);
            color: var(--white);
            font-size: 1.2rem;
            outline: none;
            transition: background 0.3s ease;
        }

        .form button:hover {
            background: linear-gradient(to right, #0073e6, #0059b3, #004080, #003366);
        }

        .form p {
            margin: 1rem 0;
            font-size: .7rem;
        }

        .form p b.pointer {
            color: var(--accent-color);
            cursor: pointer;
        }

        .form p b.pointer:hover {
            text-decoration: underline;
        }

        .flex-col {
            flex-direction: column;
        }

        .social-list {
            margin: 2rem 0;
            padding: 1rem;
            border-radius: 1.5rem;
            width: 100%;
            box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
            transform: scale(0);
            transition: transform 0.5s ease-in-out 0.2s, opacity 0.5s ease-in-out 0.2s;
            opacity: 0;
        }

        .social-list.active {
            transform: scale(1);
            opacity: 1;
        }

        .social-list>div {
            color: var(--white);
            margin: 0 .5rem;
            padding: .7rem;
            cursor: pointer;
            border-radius: .5rem;
            transform: scale(0);
            transition: transform 0.5s ease-in-out;
        }

        .social-list>div.active {
            transform: scale(1);
        }

        .social-list>div:nth-child(1) { transition-delay: 0.1s; }
        .social-list>div:nth-child(2) { transition-delay: 0.2s; }
        .social-list>div:nth-child(3) { transition-delay: 0.3s; }
        .social-list>div:nth-child(4) { transition-delay: 0.4s; }

        .social-list>div>i {
            font-size: 1.5rem;
            transition: transform 0.4s ease-in-out;
        }

        .social-list>div:hover i {
            transform: scale(1.5);
        }

        .facebook-bg { background-color: var(--facebook-color); }
        .google-bg { background-color: var(--google-color); }
        .twitter-bg { background-color: var(--twitter-color); }
        .insta-bg { background-color: var(--insta-color); }

        .pointer { cursor: pointer; }

        .content-row {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 6;
            width: 100%;
        }

        .text {
            margin: 4rem;
            color: var(--white);
            transition: transform 0.8s ease-in-out, opacity 0.8s ease-in-out;
        }

        .text h2 {
            font-size: 3.5rem;
            font-weight: 800;
            margin: 2rem 0;
        }

        .text p {
            font-weight: 600;
        }

        .img img {
            width: 30vw;
            transition: transform 0.8s ease-in-out, opacity 0.8s ease-in-out;
        }

        .container.sign-in .text.sign-in,
        .container.sign-in .img.sign-in {
            transform: translateX(0);
            opacity: 1;
        }

        .container.sign-in .text.sign-up,
        .container.sign-in .img.sign-up {
            transform: translateX(250%);
            opacity: 0;
        }

        .container.sign-up .text.sign-up,
        .container.sign-up .img.sign-up {
            transform: translateX(0);
            opacity: 1;
        }

        .container.sign-up .text.sign-in,
        .container.sign-in .img.sign-in {
            transform: translateX(-250%);
            opacity: 0;
        }

        .container::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            height: 100vh;
            width: 300vw;
            background: linear-gradient(to right, #003366, #004080, #0059b3, #0073e6);
            transition: transform 0.8s ease-in-out;
            z-index: 5;
            box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
            border-bottom-right-radius: max(50vw, 50vh);
            border-top-left-radius: max(50vw, 50vh);
        }

        .container.sign-in::before {
            transform: translateX(0);
            right: 50%;
        }

        .container.sign-up::before {
            transform: translateX(100%);
            right: 50%;
        }

        @media only screen and (max-width: 425px) {
            .container {
                overflow: visible;
            }

            .container::before,
            .container.sign-in::before,
            .container.sign-up::before {
                height: 100vh;
                border-bottom-right-radius: 0;
                border-top-left-radius: 0;
                z-index: 0;
                transform: none;
                right: 0;
                width: 100%;
                transition: none;
            }

            .col {
                width: 100%;
                position: absolute;
                padding: 2rem;
                background-color: var(--white);
                border-top-left-radius: 2rem;
                border-top-right-radius: 2rem;
                transform: translateY(100%);
                transition: transform 0.8s ease-in-out;
            }

            .container.sign-in .col.sign-in,
            .container.sign-up .col.sign-up {
                transform: translateY(0);
            }

            .row {
                align-items: flex-end;
                justify-content: flex-end;
                height: auto;
            }

            .content-row {
                align-items: flex-start;
                position: relative;
            }

            .content-row .col {
                transform: translateY(0);
                background-color: transparent;
                position: relative;
            }

            .form,
            .social-list {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }

            .text {
                margin: 0;
            }

            .text p {
                display: none;
            }

            .text h2 {
                margin: .5rem;
                font-size: 2rem;
            }

            .img img {
                width: 50vw;
            }
        }
    </style>
</head>
<body>
    <div id="container" class="container <?php echo $action === 'signup' ? 'sign-up' : 'sign-in'; ?>">
        <div class="row">
            <!-- SIGN UP -->
            <div class="col align-items-center flex-col sign-up">
                <div class="form-wrapper align-items-center">
                    <div class="form sign-up" data-form="signup">
                        <?php if (!empty($errors) && $action === 'signup'): ?>
                            <div style="color: red; margin-bottom: 1rem;">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div style="color: green; margin-bottom: 1rem;">
                                <p><?php echo $success; ?></p>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="login.php?action=signup">
                            <div class="input-group">
                                <i class='bx bxs-user'></i>
                                <input type="text" name="username" placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>
                            <div class="input-group">
                                <i class='bx bx-mail-send'></i>
                                <input type="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>
                            <div class="input-group">
                                <i class='bx bxs-lock-alt'></i>
                                <input type="password" name="password" placeholder="Password" required>
                            </div>
                            <div class="input-group">
                                <i class='bx bxs-lock-alt'></i>
                                <input type="password" name="confirm_password" placeholder="Confirm password" required>
                            </div>
                            <button type="submit" name="signup">Sign up</button>
                            <p>
                                <span>Already have an account?</span>
                                <b class="pointer" data-toggle="sign-in">Sign in here</b>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
            <!-- SIGN IN -->
            <div class="col align-items-center flex-col sign-in">
                <div class="form-wrapper align-items-center">
                    <div class="form sign-in" data-form="signin">
                        <?php if (!empty($errors) && $action === 'login'): ?>
                            <div style="color: red; margin-bottom: 1rem;">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="login.php?action=login">
                            <div class="input-group">
                                <i class='bx bxs-user'></i>
                                <input type="text" name="username" placeholder="Username" required>
                            </div>
                            <div class="input-group">
                                <i class='bx bxs-lock-alt'></i>
                                <input type="password" name="password" placeholder="Password" required>
                            </div>
                            <button type="submit" name="login">Sign in</button>
                            <p><b>Forgot password?</b></p>
                            <p>
                                <span>Don't have an account?</span>
                                <b class="pointer" data-toggle="sign-up">Sign up here</b>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row content-row">
            <div class="col align-items-center flex-col">
                <div class="text sign-in">
                    <h2>Welcome</h2>
                </div>
                <div class="img sign-in"></div>
            </div>
            <div class="col align-items-center flex-col">
                <div class="img sign-up"></div>
                <div class="text sign-up">
                    <h2>Join with us</h2>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form Toggle and Animation Control
        const container = document.getElementById('container');
        const forms = document.querySelectorAll('.form');
        const socialLists = document.querySelectorAll('.social-list');
        const toggleButtons = document.querySelectorAll('[data-toggle]');

        function toggleForm(target) {
            const isSignUp = target === 'sign-up';
            container.classList.toggle('sign-in', !isSignUp);
            container.classList.toggle('sign-up', isSignUp);

            forms.forEach(form => {
                const isActive = form.dataset.form === (isSignUp ? 'signup' : 'signin');
                form.classList.toggle('active', isActive);
            });

            socialLists.forEach(list => {
                const isActive = list.classList.contains(isSignUp ? 'sign-up' : 'sign-in');
                list.classList.toggle('active', isActive);
                list.querySelectorAll('div').forEach(div => {
                    div.classList.toggle('active', isActive);
                });
            });

            window.history.pushState({}, '', `login.php?action=${isSignUp ? 'signup' : 'login'}`);
        }

        toggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                toggleForm(button.dataset.toggle);
            });
        });

        // Initialize form state
        document.addEventListener('DOMContentLoaded', () => {
            const initialForm = '<?php echo $action === 'signup' ? 'signup' : 'signin'; ?>';
            forms.forEach(form => {
                form.classList.toggle('active', form.dataset.form === initialForm);
            });
            socialLists.forEach(list => {
                const isActive = list.classList.contains(initialForm === 'signup' ? 'sign-up' : 'sign-in');
                list.classList.toggle('active', isActive);
                list.querySelectorAll('div').forEach(div => {
                    div.classList.toggle('active', isActive);
                });
            });
        });
    </script>
</body>
</html>