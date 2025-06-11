<?php
require_once '../database/db_connect.php';
session_start();

// Check if user is logged in and has admin/staff privileges
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../login.php");
    exit();
}

$error_message = '';
$success_message = '';

try {
    // Fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Handle instrument actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $name = trim($_POST['name']);
                    $category = $_POST['category'];
                    $description = trim($_POST['description']);
                    $quantity = (int)$_POST['quantity'];

                    if (empty($name) || empty($category) || $quantity < 1) {
                        $error_message = "All fields are required and quantity must be positive";
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO instruments (name, category, description, quantity, available_quantity) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$name, $category, $description, $quantity, $quantity]);
                        $success_message = "Instrument added successfully";
                    }
                    break;

                case 'update':
                    $id = $_POST['id'];
                    $name = trim($_POST['name']);
                    $category = $_POST['category'];
                    $description = trim($_POST['description']);
                    $quantity = (int)$_POST['quantity'];
                    $status = $_POST['status'];

                    if (empty($name) || empty($category) || $quantity < 1) {
                        $error_message = "All fields are required and quantity must be positive";
                    } else {
                        // Get current available quantity
                        $stmt = $pdo->prepare("SELECT quantity, available_quantity FROM instruments WHERE id = ?");
                        $stmt->execute([$id]);
                        $current = $stmt->fetch();
                        
                        // Calculate new available quantity
                        $diff = $quantity - $current['quantity'];
                        $new_available = $current['available_quantity'] + $diff;
                        
                        if ($new_available < 0) {
                            $error_message = "Cannot reduce quantity below number of borrowed instruments";
                        } else {
                            $stmt = $pdo->prepare("
                                UPDATE instruments 
                                SET name = ?, category = ?, description = ?, 
                                    quantity = ?, available_quantity = ?, status = ?
                                WHERE id = ?
                            ");
                            $stmt->execute([$name, $category, $description, $quantity, $new_available, $status, $id]);
                            $success_message = "Instrument updated successfully";
                        }
                    }
                    break;

                case 'delete':
                    $id = $_POST['id'];
                    
                    // Check if instrument has any active borrowings
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM borrowing_records 
                        WHERE instrument_id = ? AND status = 'active'
                    ");
                    $stmt->execute([$id]);
                    
                    if ($stmt->fetchColumn() > 0) {
                        $error_message = "Cannot delete instrument with active borrowings";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM instruments WHERE id = ?");
                        $stmt->execute([$id]);
                        $success_message = "Instrument deleted successfully";
                    }
                    break;
            }
        }
    }

    // Fetch all instruments
    $stmt = $pdo->query("
        SELECT 
            i.*,
            (SELECT COUNT(*) FROM borrowing_records WHERE instrument_id = i.id AND status = 'active') as active_borrowings
        FROM instruments i
        ORDER BY i.category, i.name
    ");
    $instruments = $stmt->fetchAll();

    // Group instruments by category
    $instruments_by_category = [];
    foreach ($instruments as $instrument) {
        $instruments_by_category[$instrument['category']][] = $instrument;
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
    <title>Manage Instruments - BorrowSmart</title>
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
                        <a href="instruments.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Instruments
                        </a>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="users.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Users
                            </a>
                        <?php endif; ?>
                        <a href="reports.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
            <h1 class="text-3xl font-bold text-gray-900">Manage Instruments</h1>
            <p class="mt-2 text-gray-600">Add, edit, or remove instruments from the system</p>
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

        <!-- Add New Instrument -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Add New Instrument</h2>
            </div>
            <div class="p-6">
                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Instrument Name</label>
                            <input type="text" name="name" id="name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="category" id="category" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="brass">Brass</option>
                                <option value="woodwind">Woodwind</option>
                                <option value="percussion">Percussion</option>
                            </select>
                        </div>

                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                            <input type="number" name="quantity" id="quantity" required min="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Add Instrument
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Instrument List -->
        <?php foreach ($instruments_by_category as $category => $category_instruments): ?>
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 capitalize"><?php echo $category; ?> Instruments</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Description
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Available
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($category_instruments as $instrument): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($instrument['name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($instrument['description']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo $instrument['quantity']; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo $instrument['available_quantity']; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                            echo match($instrument['status']) {
                                                'available' => 'bg-green-100 text-green-800',
                                                'borrowed' => 'bg-yellow-100 text-yellow-800',
                                                'maintenance' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>">
                                            <?php echo ucfirst($instrument['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($instrument)); ?>)"
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                            Edit
                                        </button>
                                        <?php if ($instrument['active_borrowings'] == 0): ?>
                                            <form method="POST" action="" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this instrument?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $instrument['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </main>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Edit Instrument</h3>
            </div>
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="p-6 space-y-4">
                    <div>
                        <label for="edit_name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="edit_name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="edit_category" class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="category" id="edit_category" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="brass">Brass</option>
                            <option value="woodwind">Woodwind</option>
                            <option value="percussion">Percussion</option>
                        </select>
                    </div>

                    <div>
                        <label for="edit_description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="edit_description" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                    </div>

                    <div>
                        <label for="edit_quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                        <input type="number" name="quantity" id="edit_quantity" required min="1"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="edit_status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="edit_status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()"
                        class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(instrument) {
            document.getElementById('edit_id').value = instrument.id;
            document.getElementById('edit_name').value = instrument.name;
            document.getElementById('edit_category').value = instrument.category;
            document.getElementById('edit_description').value = instrument.description;
            document.getElementById('edit_quantity').value = instrument.quantity;
            document.getElementById('edit_status').value = instrument.status;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>
