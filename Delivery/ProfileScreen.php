<?php
session_start();
require '../config.php';

 

$delivery_user_id = $_SESSION['user_id'];

// Fetch delivery person details
$delivery_person = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'delivery'");
    $stmt->execute([$delivery_user_id]);
    $delivery_person = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$delivery_person) {
        die("Error: Delivery person details not found.");
    }
} catch (PDOException $e) {
    die("Error fetching profile: " . $e->getMessage());
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        $error = "Please fill all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone_number = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $delivery_user_id]);
            $success = "Profile updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Profile</title>
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

        input, textarea, button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--color-gray-300);
            border-radius: var(--border-radius-md);
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
    <h2>Delivery Panel</h2>
    <a href="deliveryorders.php">Pending Orders</a>
    <a href="DeliveryMap.php">Delivery Map</a>
    <a href="ProfileScreen.php">Profile</a>
    <a href="../auth/logout.php">Logout</a>
</div>

<div class="container">
    <h2>Delivery Profile</h2>

    <?php if (isset($error)): ?>
        <p class="message" style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <p class="message" style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($delivery_person['name'] ?? ''); ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($delivery_person['email'] ?? ''); ?>" required>

        <label for="phone">Phone</label>
        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($delivery_person['phone_number'] ?? ''); ?>" required>

        <label for="address">Address</label>
        <textarea id="address" name="address" required><?php echo htmlspecialchars($delivery_person['address'] ?? ''); ?></textarea>

        <button type="submit" name="update_profile">Update Profile</button>
    </form>
</div>

</body>
</html>