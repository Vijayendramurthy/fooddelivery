<?php
session_start();
include('../db_connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/loginscreen.php");
    exit();
}

$delivery_user_id = $_SESSION['user_id'];

$query = "
    SELECT 
        o.id AS order_id,
        o.location_coordinates,
        o.created_at,
        u.name AS customer_name,
        u.phone_number AS customer_phone,
        r.name AS restaurant_name,
        r.latitude AS restaurant_latitude,
        r.longitude AS restaurant_longitude,
        o.total_price
    FROM orders o
    INNER JOIN users u ON o.customer_id = u.id
    INNER JOIN users r ON o.restaurant_id = r.id AND r.role = 'restaurant'
    WHERE o.assigned_delivery_id = ? AND o.status = ''
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $delivery_user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['location_coordinates'])) {
        $location_coordinates = json_decode($row['location_coordinates'], true);
        $row['customer_latitude'] = $location_coordinates['latitude'];
        $row['customer_longitude'] = $location_coordinates['longitude'];
        $orders[] = $row;
    }
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Route Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
             margin: 0;
            padding: 0;
        }

        .wrapper {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            margin: 20px;
        }

        .map-container {
            flex: 1 1 60%;
            min-width: 300px;
            margin-right: 20px;
        }

        .map-container #map {
            width: 100%;
            height: 500px;
            border-radius: 12px;
        }

        .order-info {
            flex: 1 1 35%;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .order-list {
            margin: 20px auto;
            width: 90%;
            max-width: 1200px;
        }

        .order-item {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            cursor: pointer;
            background: #f7f7f7;
        }

        .order-item:hover {
            background-color: #ffe5d0;
        }

        .footer {
            text-align: center;
            padding: 10px;
            background-color: #FF4B3A;
            color: white;
            width: 100%;
        }

        .detail-line {
            margin: 8px 0;
        }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAHziO-HMFVGmUB3PJE_SGg3oGpznEX6hk&callback=initMap" async defer></script>
    <script>
        let map, directionsService, directionsRenderer;

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 20.5937, lng: 78.9629 },
                zoom: 5,
            });

            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer();
            directionsRenderer.setMap(map);
        }

        function showOrderOnMap(order) {
            const origin = new google.maps.LatLng(order.restaurant_lat, order.restaurant_lng);
            const destination = new google.maps.LatLng(order.customer_lat, order.customer_lng);

            directionsService.route({
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode.DRIVING,
            }, function(response, status) {
                if (status === 'OK') {
                    directionsRenderer.setDirections(response);
                } else {
                    alert("Directions request failed due to " + status);
                }
            });

            document.getElementById("orderDetails").innerHTML = `
                <h3>Order Details</h3>
                <div class="detail-line"><strong>Order ID:</strong> ${order.id}</div>
                <div class="detail-line"><strong>Customer Name:</strong> ${order.customer_name}</div>
                <div class="detail-line"><strong>Customer Phone:</strong> ${order.customer_phone}</div>
                <div class="detail-line"><strong>Restaurant:</strong> ${order.restaurant_name}</div>
                <div class="detail-line"><strong>Total Price:</strong> ₹${parseFloat(order.total_price).toFixed(2)}</div>
                <div class="detail-line"><strong>Order Date:</strong> ${order.date}</div>
                <div class="detail-line"><strong>Distance:</strong> ${order.distance.toFixed(2)} km</div>
            `;
        }
    </script>
</head>
<body>

<div class="wrapper">
    <div class="map-container">
        <div id="map"></div>
    </div>
    <div class="order-info" id="orderDetails">
        <h3>Click an order to see details</h3>
    </div>
</div>

<div class="order-list">
    <h2 style="color:white;">Accepted Orders</h2>
    <?php foreach ($orders as $order): 
        $distance = calculateDistance(
            $order['restaurant_latitude'],
            $order['restaurant_longitude'],
            $order['customer_latitude'],
            $order['customer_longitude']
        );
        ?>
        <div class="order-item" onclick='showOrderOnMap(<?= json_encode([
            "id" => $order["order_id"],
            "customer_name" => $order["customer_name"],
            "customer_phone" => $order["customer_phone"],
            "restaurant_name" => $order["restaurant_name"],
            "restaurant_lat" => floatval($order["restaurant_latitude"]),
            "restaurant_lng" => floatval($order["restaurant_longitude"]),
            "customer_lat" => floatval($order["customer_latitude"]),
            "customer_lng" => floatval($order["customer_longitude"]),
            "total_price" => floatval($order["total_price"]),
            "date" => date('F j, Y, g:i a', strtotime($order["created_at"])),
            "distance" => $distance
        ]) ?>)'>
        <strong>Order #<?= $order['order_id'] ?>:</strong> <?= $order['restaurant_name'] ?> → <?= $order['customer_name'] ?> (<?= date('M j, Y, g:i a', strtotime($order['created_at'])) ?>)
        </div>
    <?php endforeach; ?>
</div>

<footer class="footer">
    &copy; <?= date("Y") ?> Food Delivery App. All rights reserved.
</footer>

</body>
</html>
