<?php
session_start();
include('../config.php'); // ✅ Include Database Connection

// Ensure the user is logged in and `user_id` is set
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/loginscreen.php"); // Redirect to login if not logged in
    exit();
}

// Fetch the restaurant name using the restaurant_id
$restaurant_id = $_SESSION['user_id'];
$restaurant_name = '';
try {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? AND role = 'restaurant'");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($restaurant) {
        $restaurant_name = $restaurant['name'];
    } else {
        die("Error: Restaurant details not found.");
    }
} catch (PDOException $e) {
    die("Error fetching restaurant details: " . $e->getMessage());
}

// ✅ Ensure the `image_url` column exists in the `menu` table
$checkColumnQuery = "SHOW COLUMNS FROM menu LIKE 'image_url'";
$result = $pdo->query($checkColumnQuery);
if ($result->rowCount() === 0) {
    $alterTableQuery = "ALTER TABLE menu ADD COLUMN image_url VARCHAR(255) NULL";
    $pdo->exec($alterTableQuery);
}

// ✅ Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);

    // ✅ Validate fields
    if (!empty($name) && !empty($description) && !empty($price) && !empty($category)) {
        $image_url = '';

        // ✅ If image is uploaded
        if (!empty($_FILES['image']['tmp_name'])) {
            $imagePath = $_FILES['image']['tmp_name'];
            $imageName = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);

            $cloud_name = 'dtiniasq3';
            $api_key = '372822487449485';
            $api_secret = 'I8-z_AcKEYQ7ACnM79geCO44H90';
            $timestamp = time();
            $public_id = $imageName;
            $signature = sha1("public_id=$public_id&timestamp=$timestamp$api_secret");

            $postFields = [
                'file' => new CURLFile($imagePath),
                'api_key' => $api_key,
                'timestamp' => $timestamp,
                'public_id' => $public_id,
                'signature' => $signature,
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/$cloud_name/image/upload");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200) {
                $uploadResult = json_decode($response, true);
                $image_url = $uploadResult['secure_url'];
            } else {
                $message = "<p style='color: red;'>Error uploading image to Cloudinary.</p>";
            }
        }

        // ✅ Insert into `menu` table
        if (!empty($image_url)) {
            $query = "INSERT INTO menu (restaurant_id, restaurant_name, name, description, price, image_url, category) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$restaurant_id, $restaurant_name, $name, $description, $price, $image_url, $category]);

            $message = "<p style='color: green;'>Item added successfully!</p>";
        } else {
            $message = "<p style='color: red;'>Failed to upload image. Please try again.</p>";
        }
    } else {
        $message = "<p style='color: red;'>Please fill all fields!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Item</title>
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

        input, select, button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        button {
            background: var(--color-primary);
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: var(--color-primary-dark);
        }

        .message {
            text-align: center;
            font-size: 16px;
            margin-top: 10px;
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
    <h2>Add New Menu Item</h2>

    <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Item Name" required>
        <input type="text" name="description" placeholder="Description" required>
        <input type="number" name="price" placeholder="Price" step="0.01" required>
        <input type="file" name="image" accept="image/*">
        <select name="category" required>
            <option value="">Select Category</option>
            <option value="Snacks">Snacks</option>
            <option value="Juice & Shakes">Juice & Shakes</option>
            <option value="Biryani">Biryani</option>
            <option value="Fried Rice">Fried Rice</option>
            <option value="Chicken">Chicken</option>
            <option value="Mutton">Mutton</option>
            <option value="Paneer">Paneer</option>
            <option value="Full Meals">Full Meals</option>
        </select>
        <button type="submit">Add Item</button>
    </form>
</div>

</body>
</html>
