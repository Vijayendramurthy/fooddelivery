<?php
session_start();
include '../config/db.php';

if ($_SESSION['role'] != 'restaurant') {
    header('Location: ../auth/loginscreen.php');
    exit();
}

$query = "SELECT * FROM orders WHERE status != 'delivered'";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Orders</title>
</head>
<body>
    <h1>Restaurant Orders</h1>
    <table border="1">
        <tr>
            <th>Order ID</th>
            <th>Total Price</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td>₹<?= $row['total_price'] ?></td>
            <td><?= $row['status'] ?></td>
            <td>
                <form method="POST" action="update_order_status.php">
                    <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                    <select name="status">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="delivered">Delivered</option>
                    </select>
                    <button type="submit">Update</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
