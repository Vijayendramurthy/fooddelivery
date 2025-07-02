<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($pdo) || !$pdo) {
    die("Error: Database connection is missing.");
}

$orders = [];
try {
    // Fetch all orders for the logged-in customer, prioritizing pending orders
    $stmt = $pdo->prepare("
        SELECT 
            o.id AS order_id, 
            o.status, 
            o.total_price, 
            o.created_at, 
            o.location_coordinates, 
            r.name AS restaurant_name
        FROM orders o
        LEFT JOIN users r ON o.restaurant_id = r.id
        WHERE o.customer_id = ?
        ORDER BY 
            CASE 
                WHEN o.status = 'pending' THEN 1
                ELSE 2
            END, 
            o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch item details for each order
    foreach ($orders as &$order) {
        $stmtItems = $pdo->prepare("
            SELECT 
                oi.quantity, 
                oi.price, 
                m.name AS item_name, 
                m.description AS item_description, 
                m.image_url AS item_image
            FROM order_items oi
            INNER JOIN menu m ON oi.menu_id = m.id
            WHERE oi.order_id = ?
        ");
        $stmtItems->execute([$order['order_id']]);
        $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error fetching order history: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodExpress - Order History</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #ff642e;
            --color-primary-light: #ff8f69;
            --color-primary-dark: #e54b18;
            --color-secondary: #2c3e50;
            --color-accent: #3498db;
            --color-pending: #f39c12;
            --color-delivered: #27ae60;
            --color-cancelled: #e74c3c;
            --color-processing: #3498db;
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
        }

        .navbar {
            background-color: var(--color-white);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-4) var(--space-6);
            max-width: var(--max-width);
            margin: 0 auto;
        }

        .navbar-logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-left: var(--space-2);
        }

        .navbar-links {
            display: flex;
            gap: var(--space-6);
        }

        .navbar-link {
            text-decoration: none;
            color: var(--color-gray-700);
            font-weight: 500;
            font-size: 0.938rem;
            padding: var(--space-2);
            position: relative;
            transition: color var(--transition-fast);
        }

        .navbar-link:hover {
            color: var(--color-primary);
        }

        .navbar-link.active {
            color: var(--color-primary);
        }

        .navbar-link.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--color-primary);
            border-radius: 2px;
        }

        .container {
            width: 100%;
            max-width: var(--max-width);
            margin: 0 auto;
            padding: var(--space-4);
        }

        .page-title {
            margin: var(--space-8) 0;
            text-align: center;
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--color-secondary);
        }

        .page-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background-color: var(--color-primary);
            margin: var(--space-2) auto var(--space-4);
            border-radius: 4px;
        }

        .orders-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-5);
            animation: fadeIn 0.5s ease-out;
        }

        .order-card {
            background-color: var(--color-white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: transform var(--transition-normal), box-shadow var(--transition-normal);
        }

        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-4) var(--space-6);
            background-color: var(--color-gray-50);
            border-bottom: 1px solid var(--color-gray-200);
        }

        .order-id {
            font-size: 1rem;
            font-weight: 600;
            color: var(--color-gray-800);
        }

        .order-status {
            display: inline-flex;
            align-items: center;
            padding: var(--space-1) var(--space-3);
            border-radius: var(--border-radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--color-pending);
        }

        .status-processing {
            background-color: rgba(52, 152, 219, 0.15);
            color: var(--color-processing);
        }

        .status-delivered {
            background-color: rgba(39, 174, 96, 0.15);
            color: var(--color-delivered);
        }

        .status-cancelled {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--color-cancelled);
        }

        .order-body {
            padding: var(--space-6);
        }

        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-4);
            margin-bottom: var(--space-5);
        }

        .meta-item {
            margin-bottom: var(--space-3);
        }

        .meta-label {
            font-size: 0.813rem;
            font-weight: 500;
            color: var(--color-gray-500);
            margin-bottom: var(--space-1);
            display: block;
        }

        .meta-value {
            font-size: 0.938rem;
            font-weight: 600;
            color: var(--color-gray-800);
        }

        .order-items-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--color-gray-700);
            margin-bottom: var(--space-3);
            padding-bottom: var(--space-2);
            border-bottom: 1px solid var(--color-gray-200);
        }

        .order-items-list {
            list-style-type: none;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: var(--space-2) 0;
            border-bottom: 1px dashed var(--color-gray-200);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-details {
            display: flex;
            align-items: center;
        }

        .item-quantity {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background-color: var(--color-primary-light);
            color: var(--color-white);
            font-weight: 600;
            font-size: 0.813rem;
            border-radius: var(--border-radius-sm);
            margin-right: var(--space-3);
        }

        .item-name {
            font-size: 0.938rem;
            font-weight: 500;
        }

        .item-price {
            font-size: 0.938rem;
            font-weight: 600;
            color: var(--color-gray-800);
        }

        .item-description {
            font-size: 0.813rem;
            color: var(--color-gray-600);
        }

        .item-image {
            width: 60px;
            height: 60px;
            border-radius: var(--border-radius-sm);
            margin-right: var(--space-3);
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-4) var(--space-6);
            background-color: var(--color-gray-50);
            border-top: 1px solid var(--color-gray-200);
        }

        .order-total {
            font-size: 1.063rem;
            font-weight: 700;
            color: var(--color-secondary);
        }

        .order-date {
            font-size: 0.813rem;
            color: var(--color-gray-500);
        }

        .total-amount {
            color: var(--color-primary-dark);
        }

        .empty-state {
            text-align: center;
            padding: var(--space-10) var(--space-6);
            background-color: var(--color-white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            animation: fadeIn 0.5s ease-out;
        }

        .empty-state-text {
            font-size: 1.125rem;
            color: var(--color-gray-600);
            margin-bottom: var(--space-6);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-3) var(--space-6);
            background-color: var(--color-primary);
            color: var(--color-white);
            font-weight: 600;
            font-size: 0.938rem;
            border-radius: var(--border-radius-md);
            text-decoration: none;
            transition: background-color var(--transition-fast), transform var(--transition-fast);
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .button:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-2px);
            animation: pulse 1s infinite;
        }

        .navbar-mobile-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: var(--space-2);
        }

        @media (min-width: 768px) {
            .orders-container {
                grid-template-columns: repeat(auto-fill, minmax(600px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .navbar-links {
                display: none;
            }
            
            .navbar-mobile-toggle {
                display: block;
            }
            
            .navbar-links.show {
                display: flex;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                background-color: var(--color-white);
                padding: var(--space-4);
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                animation: slideDown 0.3s ease-out forwards;
            }
        }

        @media (max-width: 767px) {
            .order-header, 
            .order-body, 
            .order-footer {
                padding: var(--space-4);
            }
            
            .order-meta {
                grid-template-columns: 1fr;
                gap: var(--space-2);
            }
            
            .page-title {
                font-size: 1.5rem;
                margin: var(--space-6) 0;
            }
            
            .order-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-2);
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: var(--space-3);
            }
            
            .page-title {
                font-size: 1.25rem;
            }
            
            .order-id {
                font-size: 0.875rem;
            }
            
            .order-status {
                font-size: 0.688rem;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="homescreen.php" class="navbar-logo">
                <span class="logo-text">FoodExpress</span>
            </a>
            
            <div class="navbar-links">
                <a href="homescreen.php" class="navbar-link <?php echo basename($_SERVER['PHP_SELF']) == 'homescreen.php' ? 'active' : ''; ?>">Home</a>
                <a href="orderhistory.php" class="navbar-link <?php echo basename($_SERVER['PHP_SELF']) == 'orderhistory.php' ? 'active' : ''; ?>">Orders</a>
                <a href="profilescreen.php" class="navbar-link <?php echo basename($_SERVER['PHP_SELF']) == 'profilescreen.php' ? 'active' : ''; ?>">Profile</a>
                <a href="../auth/logout.php" class="navbar-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="page-title">Your Order History</h1>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <p class="empty-state-text">You haven't placed any orders yet.</p>
                <a href="homescreen.php" class="button">Browse Restaurants</a>
            </div>
        <?php else: ?>
            <div class="orders-container">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></span>
                            <span class="order-status"><?php echo htmlspecialchars($order['status']); ?></span>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Restaurant</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($order['restaurant_name']); ?></span>
                                </div>
                                
                                <div class="meta-item">
                                    <span class="meta-label">Delivery Location</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($order['location_coordinates']); ?></span>
                                </div>
                            </div>
                            
                            <div class="order-items">
                                <h3 class="order-items-title">Items Ordered</h3>
                                <ul class="order-items-list">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li class="order-item">
                                            <div class="item-details">
                                                <img src="<?php echo htmlspecialchars($item['item_image']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="item-image">
                                                <div>
                                                    <span class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                                    <p class="item-description"><?php echo htmlspecialchars($item['item_description']); ?></p>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="item-quantity"><?php echo $item['quantity']; ?></span>
                                                <span class="item-price">₹<?php echo number_format($item['price'], 2); ?></span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <span class="order-total">Total: <span class="total-amount">₹<?php echo number_format($order['total_price'], 2); ?></span></span>
                            <span class="order-date">Placed on <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const orderCards = document.querySelectorAll('.order-card');
            const mobileToggle = document.querySelector('.navbar-mobile-toggle');
            const navLinks = document.querySelector('.navbar-links');
            
            orderCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 100}ms`;
            });
            
            const orderStatuses = document.querySelectorAll('.order-status');
            orderStatuses.forEach(status => {
                const statusText = status.textContent.trim().toLowerCase();
                if (statusText.includes('pending')) {
                    status.classList.add('status-pending');
                } else if (statusText.includes('delivered')) {
                    status.classList.add('status-delivered');
                } else if (statusText.includes('cancelled')) {
                    status.classList.add('status-cancelled');
                } else if (statusText.includes('processing')) {
                    status.classList.add('status-processing');
                }
            });
            
            mobileToggle.addEventListener('click', () => {
                navLinks.classList.toggle('show');
            });
        });
    </script>
</body>
</html>