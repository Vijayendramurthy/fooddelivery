<?php
session_start();
include('../db_connection.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Handle adding items to the cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cartscreen'])) {
    $menu_id = $_POST['menu_id'] ?? ''; // Get menu_id
    $restaurant_id = $_POST['restaurant_id'] ?? ''; // Get restaurant_id
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $category = $_POST['category'] ?? '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($quantity < 1) {
        $quantity = 1;
    }

    // Check if the item already exists in the cart
    $query = "SELECT * FROM cart WHERE user_id = ? AND menu_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user_id, $menu_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update the quantity if item already exists
        $update_query = "UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND menu_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('iii', $quantity, $user_id, $menu_id);
        $update_stmt->execute();
    } else {
        // Add new item to the cart
        $insert_query = "INSERT INTO cart (user_id, menu_id, restaurant_id, name, description, price, image_url, category, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('iiissdssi', $user_id, $menu_id, $restaurant_id, $name, $description, $price, $image_url, $category, $quantity);
        $insert_stmt->execute();
    }

    header('Location: cartscreen.php');
    exit();
}

// ✅ Handle removing items from the cart
if (isset($_GET['remove'])) {
    $cart_id = $_GET['remove'];

    $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $cart_id, $user_id);
    $delete_stmt->execute();

    header('Location: cartscreen.php');
    exit();
}

// ✅ Display the cart
$cart_query = "
    SELECT id, name, description, price, image_url, category, quantity 
    FROM cart 
    WHERE user_id = ?";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param('i', $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            flex: 1;
            max-width: var(--max-width);
            margin: var(--space-8) auto;
            padding: var(--space-8);
            background-color: var(--color-white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-gray-900);
            margin-bottom: var(--space-6);
            text-align: center;
        }

        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-4);
            border-bottom: 1px solid var(--color-gray-200);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item img {
            width: 100px;
            height: 100px;
            border-radius: var(--border-radius-md);
            object-fit: cover;
            box-shadow: var(--shadow-sm);
        }

        .cart-item-details {
            flex: 1;
            margin-left: var(--space-6);
        }

        .cart-item h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--color-gray-900);
            margin-bottom: var(--space-2);
        }

        .cart-item p {
            font-size: 0.938rem;
            color: var(--color-gray-600);
            margin-bottom: var(--space-2);
        }

        .cart-item .price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-primary);
        }

        .cart-item a {
            font-size: 0.875rem;
            color: var(--color-primary);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .cart-item a:hover {
            color: var(--color-primary-dark);
        }

        .total {
            font-size: 1.25rem;
            font-weight: 700;
            text-align: right;
            margin-top: var(--space-6);
            color: var(--color-gray-900);
        }

        .checkout-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: var(--space-4);
            background-color: var(--color-primary);
            color: var(--color-white);
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: var(--border-radius-md);
            cursor: pointer;
            transition: background-color var(--transition-fast);
            margin-top: var(--space-6);
        }

        .checkout-btn:hover {
            background-color: var(--color-primary-dark);
        }

        .empty-cart {
            text-align: center;
            font-size: 1.125rem;
            color: var(--color-gray-600);
            margin-top: var(--space-8);
        }

        footer {
            text-align: center;
            padding: var(--space-4);
            background-color: var(--color-gray-100);
            color: var(--color-gray-600);
            font-size: 0.875rem;
        }

        #menu {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-6);
            margin-top: var(--space-8);
        }

        .item {
            flex: 1 1 calc(33.333% - var(--space-6));
            background-color: var(--color-white);
            border-radius: var(--border-radius-md);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: transform var(--transition-fast);
        }

        .item:hover {
            transform: translateY(-5px);
        }

        .item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .item-content {
            padding: var(--space-4);
        }

        .item-content h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--color-gray-900);
            margin-bottom: var(--space-2);
        }

        .item-content p {
            font-size: 0.938rem;
            color: var(--color-gray-600);
            margin-bottom: var(--space-2);
        }

        .item-content .price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: var(--space-4);
        }

        .item-content form {
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .item-content input[type="number"] {
            width: 60px;
            padding: var(--space-2);
            border: 1px solid var(--color-gray-300);
            border-radius: var(--border-radius-sm);
        }

        .item-content button {
            padding: var(--space-2) var(--space-4);
            background-color: var(--color-primary);
            color: var(--color-white);
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: background-color var(--transition-fast);
        }

        .item-content button:hover {
            background-color: var(--color-primary-dark);
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Your Cart</h2>

    <?php if ($cart_result->num_rows > 0): ?>
        <?php 
        $total_price = 0; 
        while ($cart_item = $cart_result->fetch_assoc()): 
            $total_price += $cart_item['price'] * $cart_item['quantity'];
        ?>
        <div class="cart-item">
            <img src="<?= $cart_item['image_url'] ?>" alt="<?= $cart_item['name'] ?>">
            
            <div class="cart-item-details">
                <h3><?= $cart_item['name'] ?></h3>
                <p><?= $cart_item['description'] ?></p>
                <p class="price">₹<?= $cart_item['price'] ?> x <?= $cart_item['quantity'] ?> = ₹<?= $cart_item['price'] * $cart_item['quantity'] ?></p>
                <a href="cartscreen.php?remove=<?= $cart_item['id'] ?>">Remove</a>
            </div>
        </div>
        <?php endwhile; ?>
        
        <div class="total">
            Total Price: ₹<?= $total_price ?>
        </div>

        <button class="checkout-btn" onclick="window.location.href='checkoutscreen.php'">Proceed to Checkout</button>

    <?php else: ?>
        <p class="empty-cart">Your cart is empty.</p>
    <?php endif; ?>
</div>

<div id="menu" class="menu">
    <?php
    $result = $conn->query("SELECT * FROM menu");
    while ($row = $result->fetch_assoc()) {
        echo "
        <div class='item' data-category='{$row['category']}'>
            <img src='{$row['image_url']}' alt='{$row['name']}'>
            <div class='item-content'>
                <h3>{$row['name']}</h3>
                <p>ID: {$row['id']}</p>
                <p>Restaurant ID: {$row['restaurant_id']}</p>
                <p>{$row['description']}</p>
                <p class='price'>₹{$row['price']}</p>
                <form method='POST' action='cartscreen.php'>
                    <input type='hidden' name='menu_id' value='{$row['id']}'> <!-- Pass menu_id -->
                    <input type='hidden' name='restaurant_id' value='{$row['restaurant_id']}'> <!-- Pass restaurant_id -->
                    <input type='hidden' name='name' value='{$row['name']}'>
                    <input type='hidden' name='description' value='{$row['description']}'>
                    <input type='hidden' name='price' value='{$row['price']}'>
                    <input type='hidden' name='image_url' value='{$row['image_url']}'>
                    <input type='hidden' name='category' value='{$row['category']}'>
                    <input type='number' name='quantity' value='1' min='1'>
                    <button type='submit' name='cartscreen'>Add to Cart</button>
                </form>
            </div>
        </div>";
    }
    ?>
</div>

<footer>
    &copy; <?= date("Y") ?> Food Delivery App. All Rights Reserved.
</footer>

</body>
</html>
