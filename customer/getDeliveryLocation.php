<?php
header("Content-Type: application/json");
require '../config.php';

// Fetch the delivery user's current location
$delivery_user_id = 1; // Replace with the actual delivery user ID
try {
    $stmt = $pdo->prepare("SELECT latitude, longitude FROM delivery_users WHERE id = ?");
    $stmt->execute([$delivery_user_id]);
    $delivery_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($delivery_user) {
        echo json_encode([
            "lat" => $delivery_user['latitude'],
            "lng" => $delivery_user['longitude'],
        ]);
    } else {
        echo json_encode(["error" => "Delivery user not found"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Error fetching delivery location: " . $e->getMessage()]);
}
?>