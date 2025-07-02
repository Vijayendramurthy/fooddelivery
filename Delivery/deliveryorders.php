<?php
session_start();
include '../config/db.php';

// Ensure only delivery personnel can access this page
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'delivery') {
    header('Location: ../auth/loginscreen.php'); // Redirect unauthorized users to login
    exit();
}

$delivery_id = $_SESSION['user_id'];

// ✅ Fetch only pending orders that are not yet assigned
$query = "SELECT o.id AS order_id, o.status, u.id AS user_id, u.name AS customer_name 
          FROM orders o
          JOIN users u ON o.customer_id = u.id
          WHERE o.status = 'Pending' AND o.assigned_delivery_id IS NULL";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

// ✅ Handle order acceptance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_order'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_POST['user_id'];

    // Update order status and assign delivery user
    $update_order_query = "UPDATE orders SET status = 'Accepted', assigned_delivery_id = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_order_query);
    $update_stmt->bind_param('ii', $delivery_id, $order_id);
    
    if ($update_stmt->execute()) {
        // ✅ Insert notification for the customer
        $message = "Your order #$order_id has been accepted by the delivery person.";
        $insert_notification_query = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())";
        $notification_stmt = $conn->prepare($insert_notification_query);
        $notification_stmt->bind_param('is', $user_id, $message);
        $notification_stmt->execute();

        // Refresh the page to update the order list
        header("Location: deliveryorders.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Orders</title>
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

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-gray-900);
            margin-bottom: var(--space-6);
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--space-6);
            background-color: var(--color-white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        th, td {
            padding: var(--space-4);
            text-align: left;
            border-bottom: 1px solid var(--color-gray-200);
        }

        th {
            background-color: var(--color-primary);
            color: var(--color-white);
            font-weight: 600;
        }

        tr:hover {
            background-color: var(--color-gray-50);
        }

        .btn {
            padding: var(--space-3) var(--space-4);
            background-color: var(--color-primary);
            color: var(--color-white);
            border: none;
            border-radius: var(--border-radius-md);
            cursor: pointer;
            transition: background-color var(--transition-fast);
        }

        .btn:hover {
            background-color: var(--color-primary-dark);
        }

        footer {
            text-align: center;
            padding: var(--space-4);
            background-color: var(--color-gray-100);
            color: var(--color-gray-600);
            font-size: 0.875rem;
            margin-top: var(--space-8);
        }
    </style>
</head>
<body>

<div class="side-nav">
    <h2>Delivery Panel</h2>
    <a href="deliveryorders.php">Pending Orders</a>
    <a href="DeliveryMap.php">Delivery Map</a>
    <a href="ProfileScreen.php">Profile</a>
    <a href="../auth/logout.php">Logout</a>
</div>

<div class="container">
    <h1>Pending Delivery Orders</h1>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['order_id']) ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                        <button type="submit" name="accept_order" class="btn">Accept Order</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<footer>
    &copy; <?= date("Y") ?> Food Delivery App. All Rights Reserved.
</footer>

</body>
</html>
