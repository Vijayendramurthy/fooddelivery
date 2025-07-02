<?php include('../db_connection.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodExpress - Home</title>
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

        .categories {
            display: flex;
            gap: var(--space-6);
            margin-bottom: var(--space-10);
            padding: var(--space-4) 0;
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--color-primary) var(--color-gray-100);
        }

        .categories::-webkit-scrollbar {
            height: 6px;
        }

        .categories::-webkit-scrollbar-track {
            background: var(--color-gray-100);
            border-radius: 3px;
        }

        .categories::-webkit-scrollbar-thumb {
            background-color: var(--color-primary);
            border-radius: 3px;
        }

        .category {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--space-3);
            cursor: pointer;
            transition: transform var(--transition-normal);
        }

        .category:hover {
            transform: translateY(-4px);
        }

        .category img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: var(--shadow-md);
            border: 3px solid var(--color-white);
            transition: transform var(--transition-fast);
        }

        .category:hover img {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        .category span {
            color: var(--color-gray-700);
            font-weight: 600;
            font-size: 0.938rem;
        }

        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--space-6);
        }

        .item {
            background-color: var(--color-white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: transform var(--transition-normal), box-shadow var(--transition-normal);
        }

        .item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .item-content {
            padding: var(--space-4) var(--space-6);
        }

        .item h3 {
            color: var(--color-gray-900);
            font-size: 1.25rem;
            margin-bottom: var(--space-2);
        }

        .item p {
            color: var(--color-gray-600);
            font-size: 0.938rem;
            margin-bottom: var(--space-4);
        }

        .item .price {
            color: var(--color-primary);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: var(--space-4);
        }

        .item form {
            display: flex;
            gap: var(--space-3);
            align-items: center;
        }

        .item input[type="number"] {
            width: 80px;
            padding: var(--space-2);
            border: 1px solid var(--color-gray-200);
            border-radius: var(--border-radius-md);
            font-size: 0.938rem;
        }

        .item button {
            flex: 1;
            background-color: var(--color-primary);
            color: var(--color-white);
            border: none;
            padding: var(--space-3) var(--space-4);
            border-radius: var(--border-radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: background-color var(--transition-fast);
        }

        .item button:hover {
            background-color: var(--color-primary-dark);
        }

        .section-title {
            color: var(--color-gray-900);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: var(--space-6);
            position: relative;
            padding-bottom: var(--space-3);
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--color-primary);
            border-radius: 2px;
        }

        @media (max-width: 1024px) {
            .side-nav {
                width: 240px;
            }
            .container {
                margin-left: 240px;
                max-width: calc(100% - 240px);
            }
        }

        @media (max-width: 768px) {
            .side-nav {
                width: 100%;
                height: auto;
                position: relative;
            }
            .container {
                margin-left: 0;
                max-width: 100%;
            }
            .menu {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <nav class="side-nav">
        <h2>FoodExpress</h2>
        <a href="homescreen.php">Home</a>
        <a href="orderhistory.php">Order History</a>
        <a href="profilescreen.php">Profile</a>
        <a href="ratingscreen.php">Rate Us</a>
        <a href="TrackDeliveryScreen.php">Track Delivery</a>
    </nav>

    <div class="container">
        <h2 class="section-title">Categories</h2>
        <div class="categories">
            <div class="category" onclick="filterMenu('Snacks')">
                <img src="https://images.unsplash.com/photo-1619740455993-9e612b1af08a?w=100&h=100&fit=crop" alt="Snacks">
                <span>Snacks</span>
            </div>
            <div class="category" onclick="filterMenu('Juice & Shakes')">
                <img src="https://images.unsplash.com/photo-1623065422902-30a2d299bbe4?w=100&h=100&fit=crop" alt="Juice & Shakes">
                <span>Juice & Shakes</span>
            </div>
            <div class="category" onclick="filterMenu('Biryani')">
                <img src="https://images.unsplash.com/photo-1589302168068-964664d93dc0?w=100&h=100&fit=crop" alt="Biryani">
                <span>Biryani</span>
            </div>
            <div class="category" onclick="filterMenu('Fried Rice')">
                <img src="https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=100&h=100&fit=crop" alt="Fried Rice">
                <span>Fried Rice</span>
            </div>
            <div class="category" onclick="filterMenu('Chicken')">
                <img src="https://images.unsplash.com/photo-1587593810167-a84920ea0781?w=100&h=100&fit=crop" alt="Chicken">
                <span>Chicken</span>
            </div>
            <div class="category" onclick="filterMenu('Mutton')">
                <img src="https://images.unsplash.com/photo-1603894584373-5ac82b2ae398?w=100&h=100&fit=crop" alt="Mutton">
                <span>Mutton</span>
            </div>
            <div class="category" onclick="filterMenu('Paneer')">
                <img src="https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=100&h=100&fit=crop" alt="Paneer">
                <span>Paneer</span>
            </div>
            <div class="category" onclick="filterMenu('Full Meals')">
                <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&h=100&fit=crop" alt="Full Meals">
                <span>Full Meals</span>
            </div>
        </div>

        <h2 class="section-title">Menu</h2>
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
    </div>

    <script>
        function filterMenu(category) {
            const items = document.querySelectorAll('.item');
            items.forEach(item => {
                item.style.display = item.getAttribute('data-category') === category || category === 'All' ? 'block' : 'none';
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const menuItems = document.querySelectorAll('.item');
            menuItems.forEach((item, index) => {
                item.style.animation = `fadeIn 0.3s ease-out forwards ${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>