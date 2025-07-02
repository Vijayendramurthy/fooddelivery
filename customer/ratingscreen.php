<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
if ($stmt->rowCount() === 0) {
    die("Error: User does not exist.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_rating'])) {
    $itemName = trim($_POST['item_name']);
    $rating = intval($_POST['rating']);

    if (empty($itemName) || $rating < 1 || $rating > 5) {
        $error = "Please provide a valid item name and rating.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO ratings (user_id, item, rating, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, $itemName, $rating]);
            $success = "Thank you for your rating!";
        } catch (PDOException $e) {
            $error = "Error submitting rating: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Experience</title>
    <style>
        :root {
            --color-white: #ffffff;
            --color-gray-700: #4a4a4a;
            --color-gray-50: #f9f9f9;
            --color-primary: #ff642e;
            --space-2: 8px;
            --space-4: 16px;
            --space-6: 24px;
            --space-8: 32px;
            --border-radius-md: 6px;
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.1);
            --transition-fast: 0.3s;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 0;
            display: flex;
        }

        .side-nav {
            width: 280px;
            background-color: var(--color-white);
            box-shadow: var(--shadow-md);
            padding: var(--space-6);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
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
            max-width: 600px;
            margin: 40px auto;
            margin-left: 320px; /* space for sidebar */
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }

        p {
            text-align: center;
            color: #666;
        }

        .error {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 15px;
        }

        .success {
            color: #ff642e;
            text-align: center;
            margin-bottom: 15px;
        }

        .form-group {
            margin: 20px 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .stars {
            display: flex;
            gap: 10px;
        }

        .stars input[type="radio"] {
            display: none;
        }

        .stars label {
            cursor: pointer;
        }

        .star {
            font-size: 28px;
            color: #ccc;
            transition: color 0.2s;
        }

        .stars input[type="radio"]:checked + .star {
            color: gold;
        }

        .button {
            background-color: #ff642e;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        .button:hover {
            background-color: #ff642e;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <nav class="side-nav">
        <h2>FoodExpress</h2>
        <a href="homescreen.php">Home</a>
        <a href="orderhistory.php">Order History</a>
        <a href="profilescreen.php">Profile</a>
        <a href="ratingscreen.php">Rate Us</a>
        <a href="TrackDeliveryScreen.php">Track Delivery</a>
    </nav>


    <!-- Main Content -->
    <div class="container">
        <h1>Rate Your Experience</h1>
        <p>Your feedback helps us improve our service.</p>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" placeholder="Enter item name" required>
            </div>

            <div class="form-group">
                <label for="rating">Rating</label>
                <div class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" <?php echo isset($rating) && $rating == $i ? 'checked' : ''; ?>>
                            <span class="star">&#9733;</span>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>

            <button type="submit" name="submit_rating" class="button">Submit Rating</button>
        </form>
    </div>
</body>
</html>
