<?php
session_start();
include('../db_connection.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

// ✅ Verify the user exists
$user_check_query = "SELECT id FROM users WHERE id = ?";
$user_check_stmt = $conn->prepare($user_check_query);
$user_check_stmt->bind_param('i', $user_id);
$user_check_stmt->execute();
$user_check_result = $user_check_stmt->get_result();

if ($user_check_result->num_rows === 0) {
    die("Error: User does not exist.");
}

// ✅ Fetch cart items with menu_id and restaurant_id
$cart_query = "
    SELECT c.menu_id, c.restaurant_id, c.name, c.price, c.quantity, c.category
    FROM cart c
    WHERE c.user_id = ?";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param('i', $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cartItems = [];
$totalAmount = 0;
$restaurant_id = null;

while ($row = $cart_result->fetch_assoc()) {
    $cartItems[] = $row;
    $totalAmount += $row['price'] * $row['quantity'];
    $restaurant_id = $row['restaurant_id']; // Get the restaurant_id from the cart
}

// ✅ Handle order placement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    if (empty($cartItems)) {
        echo "Cart is empty!";
        exit();
    }

    if (empty($_POST['latitude']) || empty($_POST['longitude'])) {
        die("Error: Please pin your location on the map to place an order.");
    }

    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $location_coordinates = json_encode(['latitude' => $latitude, 'longitude' => $longitude]);

    // Start the transaction
    $conn->begin_transaction();

    try {
        // ✅ Ensure `location_coordinates` column exists in `orders` table
        $check_column_query = "SHOW COLUMNS FROM orders LIKE 'location_coordinates'";
        $check_column_result = $conn->query($check_column_query);
        
        if ($check_column_result->num_rows === 0) {
            $alter_query = "ALTER TABLE orders ADD COLUMN location_coordinates VARCHAR(255) NULL";
            $conn->query($alter_query);
        }

        // ✅ Insert into `orders` table with `restaurant_id` and `location_coordinates`
        $insert_order_query = "
            INSERT INTO orders (customer_id, restaurant_id, status, total_price, created_at, location_coordinates) 
            VALUES (?, ?, 'Pending', ?, NOW(), ?)";
        $stmt = $conn->prepare($insert_order_query);
        $stmt->bind_param('iids', $user_id, $restaurant_id, $totalAmount, $location_coordinates);
        $stmt->execute();
        $orderId = $stmt->insert_id;

        // ✅ Insert order items using `menu_id`
        foreach ($cartItems as $item) {
            $insert_order_item_query = "
                INSERT INTO order_items (order_id, menu_id, quantity, price) 
                VALUES (?, ?, ?, ?)";
            $item_stmt = $conn->prepare($insert_order_item_query);
            $item_stmt->bind_param('iiid', $orderId, $item['menu_id'], $item['quantity'], $item['price']);
            $item_stmt->execute();
        }

        // ✅ Clear cart
        $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
        $clear_cart_stmt = $conn->prepare($clear_cart_query);
        $clear_cart_stmt->bind_param('i', $user_id);
        $clear_cart_stmt->execute();

        // Commit transaction
        $conn->commit();

        // ✅ Redirect to Home
        header("Location: homescreen.php?order_success=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error placing order: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
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

        h1, h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-gray-900);
            margin-bottom: var(--space-6);
            text-align: center;
        }

        #map {
            width: 100%;
            height: 400px;
            border-radius: var(--border-radius-lg);
            margin-bottom: var(--space-6);
        }

        .order-summary {
            list-style: none;
            padding: 0;
            margin: var(--space-6) 0;
        }

        .order-summary li {
            display: flex;
            justify-content: space-between;
            padding: var(--space-4);
            border-bottom: 1px solid var(--color-gray-200);
        }

        .order-summary li:last-child {
            border-bottom: none;
        }

        .order-summary strong {
            font-size: 1rem;
            color: var(--color-gray-900);
        }

        .total {
            font-size: 1.25rem;
            font-weight: 700;
            text-align: right;
            margin-top: var(--space-6);
            color: var(--color-gray-900);
        }

        .btn {
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

        .btn:hover {
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
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAHziO-HMFVGmUB3PJE_SGg3oGpznEX6hk"></script>
    <script>
        let map, marker;

        function initMap() {
            const defaultLocation = { lat: 12.9716, lng: 77.5946 }; // Default to Bangalore, India
            map = new google.maps.Map(document.getElementById("map"), {
                center: defaultLocation,
                zoom: 14,
            });

            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true, // Allow the user to drag the marker
            });

            google.maps.event.addListener(marker, 'dragend', function () {
                const position = marker.getPosition();
                document.getElementById("latitude").value = position.lat();
                document.getElementById("longitude").value = position.lng();
                document.getElementById("location-status").innerHTML = "✅ Location Selected!";
            });
        }
    </script>
</head>
<body onload="initMap()">

<div class="container">
    <h1>Checkout</h1>

    <?php if (empty($cartItems)): ?>
        <p class="empty-cart">Your cart is empty.</p>
        <a href="homescreen.php" class="btn">Browse Menu</a>
    <?php else: ?>
        <h3>Pin Your Delivery Location</h3>
        <div id="map"></div>
        <p id="location-status" style="color: red;">📍 Drag the marker to your delivery location</p>

        <form method="POST">
            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">

            <h3>Order Summary</h3>
            <ul class="order-summary">
                <?php foreach ($cartItems as $item): ?>
                    <li>
                        <div>
                            <strong><?= htmlspecialchars($item['name']) ?></strong> 
                            <br>
                            <?= $item['quantity'] ?> x ₹<?= number_format($item['price'], 2) ?>
                        </div>
                        <div>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="total">
                Total Amount: ₹<?= number_format($totalAmount, 2) ?>
            </div>

            <br><br>
            <button type="submit" name="place_order" class="btn">Place Order</button>
        </form>
    <?php endif; ?>
</div>

<footer>
    &copy; <?= date("Y") ?> Food Delivery App. All Rights Reserved.
</footer>

</body>
</html>
