<?php
require_once 'database/db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data and role
try {
    $stmt = $pdo->prepare("
        SELECT users.*, roles.name as role_name 
        FROM users 
        JOIN roles ON users.role_id = roles.id 
        WHERE users.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Fetch statistics based on role
    $stats = [];
    
    if ($user['role_name'] === 'admin') {
        $stats = [
            'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_instruments' => $pdo->query("SELECT COUNT(*) FROM instruments")->fetchColumn(),
            'active_borrowings' => $pdo->query("SELECT COUNT(*) FROM borrowing_records WHERE status = 'active'")->fetchColumn(),
            'overdue_items' => $pdo->query("SELECT COUNT(*) FROM borrowing_records WHERE status = 'active' AND expected_return_date < CURRENT_DATE")->fetchColumn()
        ];
    } elseif ($user['role_name'] === 'staff') {
        $stats = [
            'total_instruments' => $pdo->query("SELECT COUNT(*) FROM instruments")->fetchColumn(),
            'available_instruments' => $pdo->query("SELECT COUNT(*) FROM instruments WHERE status = 'available'")->fetchColumn(),
            'active_borrowings' => $pdo->query("SELECT COUNT(*) FROM borrowing_records WHERE status = 'active'")->fetchColumn(),
            'overdue_items' => $pdo->query("SELECT COUNT(*) FROM borrowing_records WHERE status = 'active' AND expected_return_date < CURRENT_DATE")->fetchColumn()
        ];
    } else {
        // Student stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_borrowed,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as currently_borrowed,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_items
            FROM borrowing_records 
            WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $borrowStats = $stmt->fetch();
        
        $stats = [
            'total_borrowed' => $borrowStats['total_borrowed'],
            'currently_borrowed' => $borrowStats['currently_borrowed'],
            'overdue_items' => $borrowStats['overdue_items'],
            'available_instruments' => $pdo->query("SELECT COUNT(*) FROM instruments WHERE status = 'available'")->fetchColumn()
        ];
    }

    // Fetch recent activities
    if ($user['role_name'] === 'admin' || $user['role_name'] === 'staff') {
        $stmt = $pdo->query("
            SELECT 
                br.id,
                u.full_name,
                i.name as instrument_name,
                br.borrow_date,
                br.expected_return_date,
                br.status
            FROM borrowing_records br
            JOIN users u ON br.user_id = u.id
            JOIN instruments i ON br.instrument_id = i.id
            ORDER BY br.borrow_date DESC
            LIMIT 5
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                br.id,
                i.name as instrument_name,
                br.borrow_date,
                br.expected_return_date,
                br.status
            FROM borrowing_records br
            JOIN instruments i ON br.instrument_id = i.id
            WHERE br.user_id = ?
            ORDER BY br.borrow_date DESC
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $recent_activities = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BorrowSmart</title>
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
                        <a href="dashboard.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <?php if ($user['role_name'] === 'admin'): ?>
                            <a href="admin/users.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Users
                            </a>
                            <a href="admin/instruments.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Instruments
                            </a>
                            <a href="admin/reports.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Reports
                            </a>
                        <?php elseif ($user['role_name'] === 'staff'): ?>
                            <a href="staff/instruments.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Instruments
                            </a>
                            <a href="staff/reports.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Reports
                            </a>
                        <?php else: ?>
                            <a href="borrow.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Borrow
                            </a>
                            <a href="history.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                History
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="profile.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['full_name']); ?>
                    </a>
                    <a href="logout.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h1>
            <p class="mt-2 text-gray-600">
                <?php
                switch ($user['role_name']) {
                    case 'admin':
                        echo "System Administrator Dashboard";
                        break;
                    case 'staff':
                        echo "Staff Dashboard";
                        break;
                    default:
                        echo "Student Dashboard";
                }
                ?>
            </p>
        </div>

        <!-- Statistics Cards -->
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <?php if ($user['role_name'] === 'admin'): ?>
                <!-- Admin Stats -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-users text-2xl text-blue-500"></i>
                            </div>
                            <div class="ml-5">
                                <div class="text-sm font-medium text-gray-500 truncate">Total Users</div>
                                <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['total_users']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-guitar text-2xl text-green-500"></i>
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
                                <i class="fas fa-exchange-alt text-2xl text-purple-500"></i>
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
            <?php elseif ($user['role_name'] === 'staff'): ?>
                <!-- Staff Stats -->
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
                                <i class="fas fa-check-circle text-2xl text-green-500"></i>
                            </div>
                            <div class="ml-5">
                                <div class="text-sm font-medium text-gray-500 truncate">Available Instruments</div>
                                <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['available_instruments']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exchange-alt text-2xl text-purple-500"></i>
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
            <?php else: ?>
                <!-- Student Stats -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-history text-2xl text-blue-500"></i>
                            </div>
                            <div class="ml-5">
                                <div class="text-sm font-medium text-gray-500 truncate">Total Borrowed</div>
                                <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['total_borrowed']; ?></div>
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
                                <div class="text-sm font-medium text-gray-500 truncate">Currently Borrowed</div>
                                <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['currently_borrowed']; ?></div>
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
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-guitar text-2xl text-purple-500"></i>
                            </div>
                            <div class="ml-5">
                                <div class="text-sm font-medium text-gray-500 truncate">Available Instruments</div>
                                <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['available_instruments']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
            <h2 class="text-lg font-medium text-gray-900">Quick Actions</h2>
            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php if ($user['role_name'] === 'admin'): ?>
                    <a href="admin/users.php" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-300">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users text-2xl text-blue-500"></i>
                                </div>
                                <div class="ml-5">
                                    <div class="text-lg font-medium text-gray-900">Manage Users</div>
                                    <div class="mt-1 text-sm text-gray-500">Add, edit, or remove system users</div>
                                </div>
                            </div>
                        </div>
                    </a>
                    <a href="admin/instruments.php" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-300">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-guitar text-2xl text-green-500"></i>
                                </div>
                                <div class="ml-5">
                                    <div class="text-lg font-medium text-gray-900">Manage Instruments</div>
                                    <div class="mt-1 text-sm text-gray-500">Update instrument inventory</div>
                                </div>
                            </div>
                        </div>
                    </a>
                    <a href="admin/reports.php" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-300">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-chart-bar text-2xl text-purple-500"></i>
                                </div>
                                <div class="ml-5">
                                    <div class="text-lg font-medium text-gray-900">View Reports</div>
                                    <div class="mt-1 text-sm text-gray-500">Access system statistics and reports</div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php elseif ($user['role_name'] === 'staff'): ?>
                    <a href="staff/instruments.php" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-300">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-guitar text-2xl text-blue-500"></i>
                                </div>
                                <div class="ml-5">
                                    <div class="text-lg font-medium text-gray-900">Manage Instruments</div>
                                    <div class="mt-1 text-sm text-gray-500">Update instrument inventory</div>
                                </div>
                            </div>
                        </div>
                    </a>
                    <a href="staff/reports.php" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-300">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-chart-bar text-2xl text-green-500"></i>
                                </div>
                                <div class="ml-5">
                                    <div class="text-lg font-medium text-gray-900">View Reports</div>
                                    <div class="mt-1 text-sm text-gray-500">Access borrowing reports</div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php else: ?>
                    <a href="borrow.php" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-300">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-guitar text-2xl text-blue-500"></i>
                                </div>
                                <div class="ml-5">
                                    <div class="text-lg font-medium text-gray-900">Borrow Instrument</div>
                                    <div class="mt-1 text-sm text-gray-500">Browse and borrow instruments</div>
                                </div>
                            </div>
                        </div>
                    </a>
                    <a href="return.php" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-300">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-undo text-2xl text-green-500"></i>
                                </div>
                                <div class="ml-5">
                                    <div class="text-lg font-medium text-gray-900">Return Instrument</div>
                                    <div class="mt-1 text-sm text-gray-500">Return borrowed instruments</div>
                                </div>
                            </div>
                        </div>
                    </a>
                    <a href="history.php" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-300">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-history text-2xl text-purple-500"></i>
                                </div>
                                <div class="ml-5">
                                    <div class="text-lg font-medium text-gray-900">View History</div>
                                    <div class="mt-1 text-sm text-gray-500">Check your borrowing history</div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="mt-8">
            <h2 class="text-lg font-medium text-gray-900">Recent Activities</h2>
            <div class="mt-4 flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <?php if ($user['role_name'] === 'admin' || $user['role_name'] === 'staff'): ?>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                User
                                            </th>
                                        <?php endif; ?>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Instrument
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Borrow Date
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Due Date
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <tr>
                                            <?php if ($user['role_name'] === 'admin' || $user['role_name'] === 'staff'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($activity['full_name']); ?>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($activity['instrument_name']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('M d, Y', strtotime($activity['borrow_date'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('M d, Y', strtotime($activity['expected_return_date'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php
                                                    echo match($activity['status']) {
                                                        'active' => 'bg-green-100 text-green-800',
                                                        'returned' => 'bg-blue-100 text-blue-800',
                                                        'overdue' => 'bg-red-100 text-red-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    };
                                                    ?>">
                                                    <?php echo ucfirst($activity['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
