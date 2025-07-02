<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userInfo = [];
try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection is missing.");
    }

    $stmt = $pdo->prepare("SELECT name, email, phone_number, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userInfo) {
        die("Error: User data not found.");
    }
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profile</title>
  <style>
    :root {
      --color-primary: #ff642e;
      --color-primary-dark: #e54b18;
      --color-gray-50: #f9fafb;
      --color-gray-100: #f3f4f6;
      --color-gray-700: #374151;
      --color-gray-800: #1f2937;
      --color-white: #ffffff;
      --space-4: 1rem;
      --space-6: 1.5rem;
      --space-8: 2rem;
      --font-family: 'Inter', sans-serif;
      --border-radius-md: 8px;
      --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: var(--font-family);
      background-color: var(--color-gray-50);
      color: var(--color-gray-800);
      display: flex;
      min-height: 100vh;
    }

    .side-nav {
      width: 260px;
      background: var(--color-white);
      padding: var(--space-6);
      box-shadow: var(--shadow-md);
      position: fixed;
      height: 100vh;
    }

    .side-nav h2 {
      color: var(--color-primary);
      font-size: 1.5rem;
      margin-bottom: var(--space-6);
      text-align: center;
    }

    .side-nav a {
      display: block;
      text-decoration: none;
      color: var(--color-gray-700);
      margin-bottom: var(--space-4);
      padding: var(--space-4);
      border-radius: var(--border-radius-md);
      transition: background 0.2s, color 0.2s;
    }

    .side-nav a:hover {
      background-color: var(--color-gray-100);
      color: var(--color-primary);
    }

    .container {
      margin-left: 260px;
      padding: var(--space-8);
      width: 100%;
    }

    h1 {
      margin-bottom: var(--space-6);
      font-size: 2rem;
    }

    .profile-card {
      background: var(--color-white);
      padding: var(--space-6);
      border-radius: var(--border-radius-md);
      box-shadow: var(--shadow-md);
      max-width: 600px;
    }

    .profile-item {
      margin-bottom: var(--space-4);
    }

    .profile-item strong {
      display: inline-block;
      width: 120px;
      color: var(--color-gray-700);
    }

    @media (max-width: 768px) {
      .side-nav {
        width: 100%;
        height: auto;
        position: relative;
      }

      .container {
        margin-left: 0;
        padding: var(--space-6);
      }
    }
  </style>
</head>
<body>

<!-- ✅ Side Navigation -->
<div class="side-nav">
  <h2>Menu</h2>
  <a href="homescreen.php">Home</a>
  <a href="orderhistory.php">Order History</a>
  <a href="profilescreen.php">Profile</a>
  <a href="ratingscreen.php">Rate Us</a>
</div>

<!-- ✅ Profile Section -->
<div class="container">
  <h1>Your Profile</h1>
  <div class="profile-card">
    <div class="profile-item"><strong>Name:</strong> <?= htmlspecialchars($userInfo['name']) ?></div>
    <div class="profile-item"><strong>Email:</strong> <?= htmlspecialchars($userInfo['email']) ?></div>
    <div class="profile-item"><strong>Phone:</strong> <?= htmlspecialchars($userInfo['phone_number']) ?></div>
    <div class="profile-item"><strong>Role:</strong> <?= htmlspecialchars($userInfo['role']) ?></div>
  </div>
  <a href="../auth/loginscreen.php" class="logout-btn">Logout</a>

</div>

</body>
</html>
