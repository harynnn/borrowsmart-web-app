<?php
require_once 'database/db_connect.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');

    try {
        // Validate matric number format (e.g., AI220000)
        if (!preg_match('/^[A-Z]{2}[0-9]{6}$/', $username)) {
            $error_message = "Invalid matric number format. Please use format like 'AI220000'";
        }
        // Check if matric number already exists
        elseif ($pdo->query("SELECT COUNT(*) FROM users WHERE username = '$username'")->fetchColumn() > 0) {
            $error_message = "This matric number is already registered";
        }
        // Validate email
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format";
        }
        // Check if email already exists
        elseif ($pdo->query("SELECT COUNT(*) FROM users WHERE email = '$email'")->fetchColumn() > 0) {
            $error_message = "This email is already registered";
        }
        // Validate password
        elseif (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters long";
        }
        elseif (!preg_match("/[A-Z]/", $password)) {
            $error_message = "Password must contain at least one uppercase letter";
        }
        elseif (!preg_match("/[a-z]/", $password)) {
            $error_message = "Password must contain at least one lowercase letter";
        }
        elseif (!preg_match("/[0-9]/", $password)) {
            $error_message = "Password must contain at least one number";
        }
        elseif (!preg_match("/[!@#$%^&*()\-_=+{};:,<.>]/", $password)) {
            $error_message = "Password must contain at least one special character";
        }
        elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match";
        }
        elseif (empty($full_name)) {
            $error_message = "Full name is required";
        }
        else {
            // All validations passed, create user
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, full_name, role_id) 
                VALUES (?, ?, ?, ?, 3)
            ");
            $stmt->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $full_name
            ]);
            $success_message = "Registration successful! You can now login.";
        }
    } catch(PDOException $e) {
        $error_message = "Registration failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BorrowSmart</title>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50 font-inter">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Create your account</h2>
                <p class="mt-2 text-sm text-gray-600">Join BorrowSmart to start borrowing instruments</p>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $success_message; ?></span>
                    <div class="mt-2">
                        <a href="login.php" class="font-medium text-green-700 underline">Click here to login</a>
                    </div>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST" action="">
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Your Matric Number</label>
                        <div class="mt-1">
                            <input id="username" name="username" type="text" required pattern="[A-Z]{2}[0-9]{6}"
                                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Enter your matric number (e.g., AI220000)">
                        </div>
                    </div>

                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <div class="mt-1">
                            <input id="full_name" name="full_name" type="text" required
                                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Enter your full name">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" required
                                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Enter your email">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required
                                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Create a password">
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <div class="mt-1">
                            <input id="confirm_password" name="confirm_password" type="password" required
                                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Confirm your password">
                        </div>
                    </div>
                </div>

                <div class="text-sm text-gray-600">
                    <p>Password must contain:</p>
                    <ul class="list-disc list-inside ml-2">
                        <li>At least 8 characters</li>
                        <li>One uppercase letter</li>
                        <li>One lowercase letter</li>
                        <li>One number</li>
                        <li>One special character</li>
                    </ul>
                </div>

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Register
                    </button>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Already have an account? 
                        <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                            Login here
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
