<?php
require_once '../database/db_connect.php';
session_start();

// Check if user is logged in and has staff privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

try {
    // Fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Fetch statistics
    $stats = [
        'total_instruments' => $pdo->query("SELECT COUNT(*) FROM instruments")->fetchColumn(),
        'available_instruments' => $pdo->query("SELECT COUNT(*) FROM instruments WHERE status = 'available'")->fetchColumn(),
        'active_borrowings' => $pdo->query("SELECT COUNT(*) FROM borrowing_records WHERE status = 'active'")->fetchColumn(),
        'overdue_items' => $pdo->query("
            SELECT COUNT(*) 
            FROM borrowing_records 
            WHERE status = 'active' AND expected_return_date < CURRENT_DATE
        ")->fetchColumn()
    ];

    // Fetch recent activity (borrowing records)
    $stmt = $pdo->prepare("
        SELECT 
            br.id,
            u.full_name,
            i.name as instrument_name,
            br.borrow_date,
            br.expected_return_date
        FROM borrowing_records br
        JOIN users u ON br.user_id = u.id
        JOIN instruments i ON br.instrument_id = i.id
        ORDER BY br.borrow_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - BorrowSmart</title>
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
                        <a href="../homepage.php" class="text-2xl font-bold text-blue-600">BorrowSmart</a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="../homepage.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Home
                        </a>
                        <a href="staff_dashboard.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="../borrow.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Borrow
                        </a>
                        <a href="../return.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Return
                        </a>
                        <a href="../history.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            History
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="../profile.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['full_name']); ?>
                    </a>
                    <a href="../logout.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
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
            <h1 class="text-3xl font-bold text-gray-900">Staff Dashboard</h1>
            <p class="mt-2 text-gray-600">Manage instruments and view system statistics</p>
        </div>

        <!-- Statistics Cards -->
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Instruments -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-guitar text-2xl text-blue-500"></i>
                        </div>
                        <div class="ml-5">
                            <div class="text-sm font-medium text-gray-500 truncate">Total Instruments</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['total_instruments']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Instruments -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-2xl text-green-500"></i>
                        </div>
                        <div class="ml-5">
                            <div class="text-sm font-medium text-gray-500 truncate">Available Instruments</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['available_instruments']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Borrowings -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exchange-alt text-2xl text-yellow-500"></i>
                        </div>
                        <div class="ml-5">
                            <div class="text-sm font-medium text-gray-500 truncate">Active Borrowings</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['active_borrowings']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Items -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
                        </div>
                        <div class="ml-5">
                            <div class="text-sm font-medium text-gray-500 truncate">Overdue Items</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['overdue_items']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="instruments.php" class="bg-white shadow rounded-lg p-6 hover:shadow-md transition-shadow duration-300">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-edit text-2xl text-blue-500"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Manage Instruments</h3>
                        <p class="text-sm text-gray-500">Add, edit, or remove instruments</p>
                    </div>
                </div>
            </a>

            <a href="reports.php" class="bg-white shadow rounded-lg p-6 hover:shadow-md transition-shadow duration-300">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chart-bar text-2xl text-green-500"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">View Reports</h3>
                        <p class="text-sm text-gray-500">View borrowing statistics and reports</p>
                    </div>
                </div>
            </a>

            <a href="history.php" class="bg-white shadow rounded-lg p-6 hover:shadow-md transition-shadow duration-300">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-history text-2xl text-gray-500"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">View Student History</h3>
                        <p class="text-sm text-gray-500">View borrowing history for students</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Recent Activity</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Student
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Instrument
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Borrow Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Expected Return
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_activity as $record): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($record['full_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($record['instrument_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($record['borrow_date'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($record['expected_return_date'])); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?php include '../footer.php'; ?>
</body>
</html>
