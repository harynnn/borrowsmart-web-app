<?php
require_once 'database/db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Fetch user data
try {
    $stmt = $pdo->prepare("
        SELECT users.*, roles.name as role_name 
        FROM users 
        JOIN roles ON users.role_id = roles.id 
        WHERE users.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'update_profile':
                    $email = trim($_POST['email'] ?? '');
                    $full_name = trim($_POST['full_name'] ?? '');

                    if (empty($email) || empty($full_name)) {
                        $error_message = "All fields are required";
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET email = ?, full_name = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$email, $full_name, $_SESSION['user_id']]);
                        $success_message = "Profile updated successfully";
                    }
                    break;

                case 'change_password':
                    $current_password = $_POST['current_password'] ?? '';
                    $new_password = $_POST['new_password'] ?? '';
                    $confirm_password = $_POST['confirm_password'] ?? '';

                    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                        $error_message = "All password fields are required";
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = "New passwords do not match";
                    } elseif (!password_verify($current_password, $user['password'])) {
                        $error_message = "Current password is incorrect";
                    } else {
                        // Validate new password
                        if (strlen($new_password) < 8) {
                            $error_message = "Password must be at least 8 characters long";
                        } elseif (!preg_match("/[A-Z]/", $new_password)) {
                            $error_message = "Password must contain at least one uppercase letter";
                        } elseif (!preg_match("/[a-z]/", $new_password)) {
                            $error_message = "Password must contain at least one lowercase letter";
                        } elseif (!preg_match("/[0-9]/", $new_password)) {
                            $error_message = "Password must contain at least one number";
                        } elseif (!preg_match("/[!@#$%^&*()\-_=+{};:,<.>]/", $new_password)) {
                            $error_message = "Password must contain at least one special character";
                        } else {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET password = ?
                                WHERE id = ?
                            ");
                            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                            $success_message = "Password changed successfully";
                        }
                    }
                    break;
            }
        } catch(PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - BorrowSmart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50 font-inter">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="homepage.php" class="text-2xl font-bold text-blue-600">BorrowSmart</a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="homepage.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Home
                        </a>
                        <a href="dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="logout.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Profile Settings</h1>
            <p class="mt-2 text-gray-600">Manage your account information and password</p>
        </div>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Profile Information -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Profile Information</h3>
                    <form method="POST" action="" class="mt-5 space-y-4">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div>
                            <label for="matric" class="block text-sm font-medium text-gray-700">Matric Number</label>
                            <input type="text" id="matric" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3 text-gray-700" 
                                disabled>
                        </div>

                        <div>
                            <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="full_name" id="full_name" 
                                value="<?php echo htmlspecialchars($user['full_name']); ?>" required
                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="email" 
                                value="<?php echo htmlspecialchars($user['email']); ?>" required
                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            <input type="text" id="role" value="<?php echo ucfirst(htmlspecialchars($user['role_name'])); ?>" 
                                class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3 text-gray-700" 
                                disabled>
                        </div>

                        <div>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Change Password</h3>
                    <form method="POST" action="" class="mt-5 space-y-4">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input type="password" name="current_password" id="current_password" required
                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" name="new_password" id="new_password" required
                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required
                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
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
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
