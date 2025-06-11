<?php
require_once 'database/db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

    // Fetch borrowing history
    $stmt = $pdo->prepare("
        SELECT 
            br.id,
            br.borrow_date,
            br.expected_return_date,
            br.actual_return_date,
            br.status,
            br.notes,
            i.name as instrument_name,
            i.category
        FROM borrowing_records br
        JOIN instruments i ON br.instrument_id = i.id
        WHERE br.user_id = ?
        ORDER BY br.borrow_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $borrowing_history = $stmt->fetchAll();

    // Calculate statistics
    $total_borrowed = count($borrowing_history);
    $returned_on_time = 0;
    $returned_late = 0;
    $currently_borrowed = 0;

    foreach ($borrowing_history as $record) {
        if ($record['status'] === 'returned') {
            if (strtotime($record['actual_return_date']) <= strtotime($record['expected_return_date'])) {
                $returned_on_time++;
            } else {
                $returned_late++;
            }
        } elseif ($record['status'] === 'active') {
            $currently_borrowed++;
        }
    }

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing History - BorrowSmart</title>
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
                        <a href="borrow.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Borrow
                        </a>
                        <a href="history.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            History
                        </a>
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
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Borrowing History</h1>
            <p class="mt-2 text-gray-600">View your complete instrument borrowing history</p>
        </div>

        <!-- Statistics Cards -->
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Borrowed -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-history text-2xl text-blue-500"></i>
                        </div>
                        <div class="ml-5">
                            <div class="text-sm font-medium text-gray-500 truncate">Total Borrowed</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $total_borrowed; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Currently Borrowed -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-2xl text-green-500"></i>
                        </div>
                        <div class="ml-5">
                            <div class="text-sm font-medium text-gray-500 truncate">Currently Borrowed</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $currently_borrowed; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Returned On Time -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-2xl text-green-500"></i>
                        </div>
                        <div class="ml-5">
                            <div class="text-sm font-medium text-gray-500 truncate">Returned On Time</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $returned_on_time; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Returned Late -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-2xl text-red-500"></i>
                        </div>
                        <div class="ml-5">
                            <div class="text-sm font-medium text-gray-500 truncate">Returned Late</div>
                            <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $returned_late; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Borrowing History Table -->
        <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Detailed History</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Instrument
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Borrow Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Expected Return
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actual Return
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($borrowing_history as $record): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($record['instrument_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 capitalize">
                                        <?php echo htmlspecialchars($record['category']); ?>
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php 
                                        if ($record['actual_return_date']) {
                                            echo date('M d, Y', strtotime($record['actual_return_date']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                        echo match($record['status']) {
                                            'active' => 'bg-green-100 text-green-800',
                                            'returned' => 'bg-blue-100 text-blue-800',
                                            'overdue' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
