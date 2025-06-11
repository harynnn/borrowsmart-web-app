<?php
require_once '../database/db_connect.php';
session_start();

// Check if user is logged in and has admin/staff privileges
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../login.php");
    exit();
}

try {
    // Fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Get date range from query parameters or default to current month
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');

    // Fetch overall statistics
    $stats = [
        'total_instruments' => $pdo->query("SELECT COUNT(*) FROM instruments")->fetchColumn(),
        'total_borrowings' => $pdo->query("SELECT COUNT(*) FROM borrowing_records")->fetchColumn(),
        'active_borrowings' => $pdo->query("SELECT COUNT(*) FROM borrowing_records WHERE status = 'active'")->fetchColumn(),
        'overdue_items' => $pdo->query("
            SELECT COUNT(*) 
            FROM borrowing_records 
            WHERE status = 'active' AND expected_return_date < CURRENT_DATE
        ")->fetchColumn()
    ];

    // Fetch borrowing statistics by category for the selected period
    $stmt = $pdo->prepare("
        SELECT 
            i.category,
            COUNT(*) as total_borrowings,
            COUNT(CASE WHEN br.status = 'active' THEN 1 END) as active_borrowings,
            COUNT(CASE WHEN br.status = 'returned' AND br.actual_return_date > br.expected_return_date THEN 1 END) as late_returns
        FROM borrowing_records br
        JOIN instruments i ON br.instrument_id = i.id
        WHERE br.borrow_date BETWEEN ? AND ?
        GROUP BY i.category
    ");
    $stmt->execute([$start_date, $end_date]);
    $category_stats = $stmt->fetchAll();

    // Fetch most borrowed instruments
    $stmt = $pdo->prepare("
        SELECT 
            i.name,
            i.category,
            COUNT(*) as borrow_count
        FROM borrowing_records br
        JOIN instruments i ON br.instrument_id = i.id
        WHERE br.borrow_date BETWEEN ? AND ?
        GROUP BY i.id
        ORDER BY borrow_count DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $popular_instruments = $stmt->fetchAll();

    // Fetch users with most borrowings
    $stmt = $pdo->prepare("
        SELECT 
            u.full_name,
            u.username,
            COUNT(*) as borrow_count,
            COUNT(CASE WHEN br.status = 'active' THEN 1 END) as active_borrowings,
            COUNT(CASE WHEN br.status = 'returned' AND br.actual_return_date > br.expected_return_date THEN 1 END) as late_returns
        FROM borrowing_records br
        JOIN users u ON br.user_id = u.id
        WHERE br.borrow_date BETWEEN ? AND ?
        GROUP BY u.id
        ORDER BY borrow_count DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_borrowers = $stmt->fetchAll();

    // Fetch recent overdue items
    $stmt = $pdo->query("
        SELECT 
            br.id,
            u.full_name,
            u.username,
            i.name as instrument_name,
            i.category,
            br.borrow_date,
            br.expected_return_date,
            DATEDIFF(CURRENT_DATE, br.expected_return_date) as days_overdue
        FROM borrowing_records br
        JOIN users u ON br.user_id = u.id
        JOIN instruments i ON br.instrument_id = i.id
        WHERE br.status = 'active' 
        AND br.expected_return_date < CURRENT_DATE
        ORDER BY br.expected_return_date ASC
        LIMIT 10
    ");
    $overdue_items = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - BorrowSmart</title>
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
                        <a href="dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="instruments.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Instruments
                        </a>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="users.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Users
                            </a>
                        <?php endif; ?>
                        <a href="reports.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Reports
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
            <h1 class="text-3xl font-bold text-gray-900">Reports & Statistics</h1>
            <p class="mt-2 text-gray-600">View detailed borrowing statistics and reports</p>
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" id="start_date" 
                            value="<?php echo $start_date; ?>"
                            class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" id="end_date" 
                            value="<?php echo $end_date; ?>"
                            class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Overall Statistics -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
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

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exchange-alt text-2xl text-green-500"></i>
                        </div>
                        <div class="ml-5">
                            <div class="text-sm font-medium text-gray-500 truncate">Total Borrowings</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['total_borrowings']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-2xl text-yellow-500"></i>
                        </div>
                        <div class="ml-5">
                            <div class="text-sm font-medium text-gray-500 truncate">Active Borrowings</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['active_borrowings']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

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

        <!-- Category Statistics -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Borrowing Statistics by Category</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($category_stats as $stat): ?>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 capitalize mb-4"><?php echo $stat['category']; ?></h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Total Borrowings:</span>
                                    <span class="text-sm font-medium"><?php echo $stat['total_borrowings']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Active Borrowings:</span>
                                    <span class="text-sm font-medium"><?php echo $stat['active_borrowings']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Late Returns:</span>
                                    <span class="text-sm font-medium"><?php echo $stat['late_returns']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Most Borrowed Instruments -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Most Borrowed Instruments</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($popular_instruments as $instrument): ?>
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($instrument['name']); ?></div>
                                    <div class="text-sm text-gray-500 capitalize"><?php echo $instrument['category']; ?></div>
                                </div>
                                <div class="text-sm font-medium">
                                    <?php echo $instrument['borrow_count']; ?> borrowings
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Top Borrowers -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Top Borrowers</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($top_borrowers as $borrower): ?>
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($borrower['full_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($borrower['username']); ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium"><?php echo $borrower['borrow_count']; ?> borrowings</div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo $borrower['late_returns']; ?> late returns
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Items -->
        <?php if (!empty($overdue_items)): ?>
            <div class="mt-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Overdue Items</h2>
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
                                    Due Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Days Overdue
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($overdue_items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['full_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['username']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['instrument_name']); ?></div>
                                        <div class="text-sm text-gray-500 capitalize"><?php echo $item['category']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo date('M d, Y', strtotime($item['expected_return_date'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            <?php echo $item['days_overdue']; ?> days
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
