<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $gmail = trim($_POST['gmail']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE gmail = ?");
    $stmt->execute([$gmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'customer') {
            header("Location: ../customer/Homescreen.php");
        } elseif ($user['role'] === 'restaurant') {
            header("Location: ../Restaurant/AddItemScreen.php");
        } elseif ($user['role'] === 'delivery') {
            header("Location: ../Delivery/DeliveryOrders.php");
        } else {
            header("Location: home.php");
        }
        exit();
    } else {
        die("Invalid gmail or password.");
    }
}
?>
