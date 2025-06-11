<?php
require_once 'database/db_connect.php';
session_start();

// Fetch user details if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BorrowSmart - Homepage</title>
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
                        <a href="homepage.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Home
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                        <?php endif; ?>
                        <a href="borrow.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Borrow
                        </a>
                        <a href="return.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Return
                        </a>
                        <a href="history.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            History
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['full_name']); ?>
                        </a>
                        <a href="logout.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Hero Section -->
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h1 class="text-3xl font-extrabold text-gray-900">Welcome to BorrowSmart</h1>
                <p class="mt-3 max-w-2xl text-xl text-gray-500">
                    Your one-stop solution for managing instrument borrowing and lending.
                </p>
                <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                    <div class="rounded-md shadow">
                        <a href="login.php" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10">
                            Start Borrowing
                        </a>
                    </div>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="mt-3 sm:mt-0 sm:ml-3">
                            <a href="register.php" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 md:py-4 md:text-lg md:px-10">
                                Register
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <img class="w-full object-cover" src="https://w0.peakpx.com/wallpaper/125/163/HD-wallpaper-musical-instruments-saxaphone-violin-guitar-trumpet.jpg" alt="Musical Instruments">
        </div>

        <!-- Features Section -->
        <div class="mt-12">
            <h2 class="text-3xl font-extrabold text-gray-900">Key Features</h2>
            <p class="mt-3 max-w-2xl text-xl text-gray-500">
                Explore the features that make BorrowSmart the perfect solution for instrument management.
            </p>

            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-900">Easy Instrument Browsing</h3>
                    <p class="mt-2 text-gray-600">
                        Browse available instruments with detailed descriptions and images.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-900">Simple Borrowing Process</h3>
                    <p class="mt-2 text-gray-600">
                        Request instruments with just a few clicks and track your borrowing history.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-900">Real-time Availability</h3>
                    <p class="mt-2 text-gray-600">
                        Check instrument availability in real-time to plan your practice sessions.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-900">Automated Reminders</h3>
                    <p class="mt-2 text-gray-600">
                        Receive automated reminders for upcoming returns to avoid overdue.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-900">Staff Management Tools</h3>
                    <p class="mt-2 text-gray-600">
                        Manage instruments, track borrowing records, and generate reports with ease.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-900">Secure and Reliable</h3>
                    <p class="mt-2 text-gray-600">
                        Your data is safe with our secure and reliable platform.
                    </p>
                </div>
            </div>
        </div>
    </main>
     <?php include 'footer.php'; ?>
</body>
</html>
