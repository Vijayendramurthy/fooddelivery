<?php
session_start();
require '../config.php';

 

// Fetch menu items for the logged-in restaurant
$items = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM menu WHERE restaurantId = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching menu items: " . $e->getMessage());
}

// Handle add/update item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_item'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $itemId = isset($_POST['item_id']) ? intval($_POST['item_id']) : null;

    if (empty($name) || empty($description) || $price <= 0) {
        $error = "Please fill all fields correctly.";
    } else {
        try {
            if ($itemId) {
                // Update existing item
                $stmt = $pdo->prepare("UPDATE menu SET name = ?, description = ?, price = ? WHERE id = ? AND restaurantId = ?");
                $stmt->execute([$name, $description, $price, $itemId, $_SESSION['user_id']]);
                $success = "Item updated successfully!";
            } else {
                // Add new item
                $stmt = $pdo->prepare("INSERT INTO menu (name, description, price, restaurantEmail, restaurantId) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $_SESSION['user_email'], $_SESSION['user_id']]);
                $success = "Item added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error saving item: " . $e->getMessage();
        }
    }
}

// Handle delete item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_item'])) {
    $itemId = intval($_POST['item_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM menu WHERE id = ? AND restaurantId = ?");
        $stmt->execute([$itemId, $_SESSION['user_id']]);
        $success = "Item deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting item: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Items</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="container">
        <h1>Manage Items</h1>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="item_id" value="<?php echo isset($itemId) ? htmlspecialchars($itemId) : ''; ?>">
            <div>
                <label for="name">Item Name</label>
                <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            </div>
            <div>
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
            <div>
                <label for="price">Price</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo isset($price) ? htmlspecialchars($price) : ''; ?>" required>
            </div>
            <button type="submit" name="save_item">Save Item</button>
        </form>

        <h2>Menu Items</h2>
        <?php if (empty($items)): ?>
            <p>No items found.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($items as $item): ?>
                    <li>
                        <p><strong><?php echo htmlspecialchars($item['name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                        <p>Price: ₹<?php echo number_format($item['price'], 2); ?></p>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="delete_item">Delete</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
