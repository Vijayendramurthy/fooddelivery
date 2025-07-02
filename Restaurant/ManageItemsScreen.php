<?php
session_start();
require '../config.php';

// Handle Edit Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {
    $item_id = $_POST['item_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);

    try {
        $stmt = $pdo->prepare("UPDATE menu SET name = ?, description = ?, price = ?, category = ? WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$name, $description, $price, $category, $item_id, $_SESSION['user_id']]);
        $success_message = "Item updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error updating item: " . $e->getMessage();
    }
}

// Handle Delete Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    $item_id = $_POST['item_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM menu WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$item_id, $_SESSION['user_id']]);
        $success_message = "Item deleted successfully!";
    } catch (PDOException $e) {
        $error_message = "Error deleting item: " . $e->getMessage();
    }
}

// Fetch menu items for the logged-in restaurant
$items = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM menu WHERE restaurant_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching menu items: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Items</title>
    <style>
        :root {
            --color-primary: #ff642e;
            --color-primary-light: #ff8f69;
            --color-primary-dark: #e54b18;
            --color-secondary: #2c3e50;
            --color-accent: #3498db;
            --color-white: #ffffff;
            --color-gray-50: #f9fafb;
            --color-gray-100: #f3f4f6;
            --color-gray-200: #e5e7eb;
            --color-gray-300: #d1d5db;
            --color-gray-400: #9ca3af;
            --color-gray-500: #6b7280;
            --color-gray-600: #4b5563;
            --color-gray-700: #374151;
            --color-gray-800: #1f2937;
            --color-gray-900: #111827;
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-10: 2.5rem;
            --space-12: 3rem;
            --space-16: 4rem;
            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            --line-height-body: 1.5;
            --line-height-heading: 1.2;
            --border-radius-sm: 6px;
            --border-radius-md: 8px;
            --border-radius-lg: 12px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition-fast: 150ms ease;
            --transition-normal: 250ms ease;
            --max-width: 1120px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            font-size: 16px;
            line-height: var(--line-height-body);
            color: var(--color-gray-800);
            background-color: var(--color-gray-50);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            min-height: 100vh;
        }

        .side-nav {
            width: 280px;
            background-color: var(--color-white);
            box-shadow: var(--shadow-md);
            padding: var(--space-6);
            height: 100vh;
            position: fixed;
        }

        .side-nav h2 {
            color: var(--color-primary);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: var(--space-8);
            text-align: center;
        }

        .side-nav a {
            display: block;
            color: var(--color-gray-700);
            text-decoration: none;
            padding: var(--space-4);
            margin: var(--space-2) 0;
            border-radius: var(--border-radius-md);
            font-weight: 500;
            transition: all var(--transition-fast);
        }

        .side-nav a:hover {
            background-color: var(--color-gray-50);
            color: var(--color-primary);
            transform: translateX(var(--space-2));
        }

        .container {
            flex: 1;
            margin-left: 280px;
            padding: var(--space-8);
            max-width: calc(100% - 280px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--space-6);
        }

        table th, table td {
            border: 1px solid var(--color-gray-200);
            padding: var(--space-4);
            text-align: left;
        }

        table th {
            background-color: var(--color-gray-100);
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: var(--space-2);
        }

        .action-buttons button {
            padding: var(--space-2) var(--space-4);
            font-size: 14px;
            border: none;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: background-color var(--transition-fast);
        }

        .action-buttons button:hover {
            background-color: var(--color-gray-200);
        }

        .action-buttons button.delete {
            background-color: var(--color-primary);
            color: var(--color-white);
        }

        .action-buttons button.delete:hover {
            background-color: var(--color-primary-dark);
        }
    </style>
</head>
<body>

<div class="side-nav">
    <h2>Restaurant Panel</h2>
    <a href="ManageItemsScreen.php">Manage Items</a>
    <a href="AddItemScreen.php">Add Item</a>
    <a href="ProfileScreen.php">Profile</a>
    <a href="../auth/logout.php">Logout</a>
</div>

<div class="container">
    <h2>Manage Menu Items</h2>

    <?php if (!empty($success_message)): ?>
        <p style="color: green;"><?php echo $success_message; ?></p>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <p>No items found. <a href="AddItemScreen.php">Add your first item</a>.</p>
    <?php else: ?>
        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                            </td>
                            <td>
                                <input type="text" name="description" value="<?php echo htmlspecialchars($item['description']); ?>" required>
                            </td>
                            <td>
                                <input type="number" name="price" value="<?php echo htmlspecialchars($item['price']); ?>" step="0.01" required>
                            </td>
                            <td>
                                <select name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Snacks" <?php echo $item['category'] === 'Snacks' ? 'selected' : ''; ?>>Snacks</option>
                                    <option value="Juice & Shakes" <?php echo $item['category'] === 'Juice & Shakes' ? 'selected' : ''; ?>>Juice & Shakes</option>
                                    <option value="Biryani" <?php echo $item['category'] === 'Biryani' ? 'selected' : ''; ?>>Biryani</option>
                                    <option value="Fried Rice" <?php echo $item['category'] === 'Fried Rice' ? 'selected' : ''; ?>>Fried Rice</option>
                                    <option value="Chicken" <?php echo $item['category'] === 'Chicken' ? 'selected' : ''; ?>>Chicken</option>
                                    <option value="Mutton" <?php echo $item['category'] === 'Mutton' ? 'selected' : ''; ?>>Mutton</option>
                                    <option value="Paneer" <?php echo $item['category'] === 'Paneer' ? 'selected' : ''; ?>>Paneer</option>
                                    <option value="Full Meals" <?php echo $item['category'] === 'Full Meals' ? 'selected' : ''; ?>>Full Meals</option>
                                </select>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <!-- Save Changes Button -->
                                    <button type="submit" name="save_changes" class="save">Save Changes</button>
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">

                                    <!-- Delete Button -->
                                    <button type="submit" name="delete_item" class="delete">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
