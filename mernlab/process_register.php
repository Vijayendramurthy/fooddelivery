<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $phone_number = trim($_POST['phone_number']);
    $gmail = trim($_POST['gmail']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = trim($_POST['role']);

    // Check if gmail already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE gmail = ?");
    $stmt->execute([$gmail]);

    if ($stmt->rowCount() > 0) {
        die("Gmail already in use.");
    }

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (name, phone_number, gmail, password, role) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $phone_number, $gmail, $password, $role])) {
        header("Location: auth/login.php?register=success");
        exit();
    } else {
        die("Registration failed.");
    }
}
?>
