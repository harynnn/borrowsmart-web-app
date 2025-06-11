<?php
require_once 'database/db_connect_secure.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = "Invalid request";
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        try {
            // Check rate limiting
            if (!checkRateLimit($ip_address)) {
                throw new Exception("Too many login attempts. Please try again later.");
            }

            // Check if user is locked out
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $user['lockout_time'] && strtotime($user['lockout_time']) > time()) {
                throw new Exception("Account is temporarily locked. Please try again later.");
            }

            // Verify credentials
            $stmt = $pdo->prepare("
                SELECT users.*, roles.name as role 
                FROM users 
                JOIN roles ON users.role_id = roles.id 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Log login attempt
            $stmt = $pdo->prepare("
                INSERT INTO login_logs (user_id, ip_address, success, user_agent) 
                VALUES (?, ?, ?, ?)
            ");

            if ($user && verifyPassword($password, $user['password'])) {
                // Reset login attempts on successful login
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET login_attempts = 0, 
                        lockout_time = NULL,
                        last_login = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);

                // Log successful login
                $stmt->execute([$user['id'], $ip_address, true, $_SERVER['HTTP_USER_AGENT']]);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['LAST_ACTIVITY'] = time();
                
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } elseif ($user['role'] === 'staff') {
                    header("Location: staff/staff_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                // Log failed login attempt
                if ($user) {
                    $stmt->execute([$user['id'], $ip_address, false, $_SERVER['HTTP_USER_AGENT']]);
                    
                    // Increment login attempts
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET login_attempts = login_attempts + 1,
                            lockout_time = CASE 
                                WHEN login_attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                                ELSE NULL 
                            END
                        WHERE id = ?
                    ");
                    $stmt->execute([MAX_LOGIN_ATTEMPTS, LOCKOUT_TIME, $user['id']]);
                }
                
                $error_message = "Invalid username or password";
            }
        } catch(Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BorrowSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50 font-inter">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Welcome back</h2>
                <p class="mt-2 text-sm text-gray-600">Sign in to your BorrowSmart account</p>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo escapeHTML($error_message); ?></span>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Your ID</label>
                        <div class="mt-1">
                            <input id="username" name="username" type="text" required
                                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Enter matric number or staff ID (e.g., AI220000 or STAFF0001)">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required
                                class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Enter your password">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="forgotpassword.php" class="font-medium text-blue-600 hover:text-blue-500">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Sign in
                    </button>
                </div>

                <div class="text-center space-y-4">
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                            Register here
                        </a>
                    </p>
                    
                    <!-- Default Account Information -->
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Default Accounts:</h3>
                        <div class="space-y-2 text-xs text-gray-600">
                            <p><strong>Admin:</strong> ADMIN0001</p>
                            <p><strong>Staff:</strong> STAFF0001</p>
                            <p><strong>Password for both:</strong> Admin@123</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
