<?php
require_once 'database/db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';

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

    // Fetch active borrowings
    $stmt = $pdo->prepare("
        SELECT 
            br.id,
            br.borrow_date,
            br.expected_return_date,
            i.name as instrument_name,
            i.id as instrument_id,
            i.category,
            CASE 
                WHEN br.expected_return_date < CURRENT_DATE THEN 'overdue'
                ELSE 'active'
            END as current_status
        FROM borrowing_records br
        JOIN instruments i ON br.instrument_id = i.id
        WHERE br.user_id = ? AND br.status = 'active'
        ORDER BY br.borrow_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $active_borrowings = $stmt->fetchAll();

    // Handle return request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return'])) {
        $borrowing_id = $_POST['borrowing_id'];
        $instrument_id = $_POST['instrument_id'];
        $condition = $_POST['condition'];
        $notes = $_POST['notes'] ?? '';

        try {
            // Start transaction
            $pdo->beginTransaction();

            // Update borrowing record
            $stmt = $pdo->prepare("
                UPDATE borrowing_records 
                SET 
                    status = 'returned',
                    actual_return_date = CURRENT_DATE,
                    notes = CONCAT(COALESCE(notes, ''), '\nReturn condition: ', ?)
                WHERE id = ?
            ");
            $stmt->execute([$condition . ($notes ? " - " . $notes : ""), $borrowing_id]);

            // Update instrument availability
            $stmt = $pdo->prepare("
                UPDATE instruments 
                SET 
                    available_quantity = available_quantity + 1,
                    status = CASE 
                        WHEN ? = 'damaged' THEN 'maintenance'
                        ELSE status 
                    END
                WHERE id = ?
            ");
            $stmt->execute([$condition, $instrument_id]);

            $pdo->commit();
            $success_message = "Instrument returned successfully";
            
            // Refresh page
            header("Location: return.php?success=1");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error processing return: " . $e->getMessage();
        }
    }
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Instrument - BorrowSmart</title>
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
                        <a href="return.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Return
                        </a>
                        <a href="history.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
            <h1 class="text-3xl font-bold text-gray-900">Return Instrument</h1>
            <p class="mt-2 text-gray-600">Return your borrowed instruments</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">Instrument returned successfully!</span>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <!-- Active Borrowings -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Currently Borrowed Instruments</h2>
            </div>
            <div class="p-6">
                <?php if (empty($active_borrowings)): ?>
                    <p class="text-gray-600">You have no active borrowings.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-6">
                        <?php foreach ($active_borrowings as $borrowing): ?>
                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($borrowing['instrument_name']); ?>
                                        </h3>
                                        <p class="text-sm text-gray-600 mt-1 capitalize">
                                            Category: <?php echo htmlspecialchars($borrowing['category']); ?>
                                        </p>
                                    </div>
                                    <div class="mt-2 sm:mt-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $borrowing['current_status'] === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo ucfirst($borrowing['current_status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Borrowed Date:</p>
                                        <p class="font-medium"><?php echo date('M d, Y', strtotime($borrowing['borrow_date'])); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Expected Return Date:</p>
                                        <p class="font-medium"><?php echo date('M d, Y', strtotime($borrowing['expected_return_date'])); ?></p>
                                    </div>
                                </div>
                                <form method="POST" action="" class="mt-4">
                                    <input type="hidden" name="borrowing_id" value="<?php echo $borrowing['id']; ?>">
                                    <input type="hidden" name="instrument_id" value="<?php echo $borrowing['instrument_id']; ?>">
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="condition_<?php echo $borrowing['id']; ?>" class="block text-sm font-medium text-gray-700">
                                                Instrument Condition
                                            </label>
                                            <select id="condition_<?php echo $borrowing['id']; ?>" name="condition" required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                <option value="good">Good</option>
                                                <option value="fair">Fair</option>
                                                <option value="damaged">Damaged</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="notes_<?php echo $borrowing['id']; ?>" class="block text-sm font-medium text-gray-700">
                                                Notes (Optional)
                                            </label>
                                            <input type="text" id="notes_<?php echo $borrowing['id']; ?>" name="notes"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                placeholder="Any comments about the instrument's condition">
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button type="submit" name="return"
                                            class="w-full sm:w-auto bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                            Return Instrument
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
