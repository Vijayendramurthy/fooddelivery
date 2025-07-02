<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch the customer's destination coordinates, restaurant location, and delivery user location
$user_id = $_SESSION['user_id'];
$destination_lat = 0;
$destination_lng = 0;
$restaurant_lat = 0;
$restaurant_lng = 0;
$delivery_user_lat = 0;
$delivery_user_lng = 0;

try {
    // Fetch the customer's delivery destination from the latest order
    $stmt = $pdo->prepare("SELECT location_coordinates, restaurant_id FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Error: No orders found for the user. Please place an order first.");
    }

    if (empty($order['location_coordinates'])) {
        die("Error: Location coordinates are missing in the order.");
    }

    $destination_coordinates = json_decode($order['location_coordinates'], true);
    if (!$destination_coordinates || !isset($destination_coordinates['latitude']) || !isset($destination_coordinates['longitude'])) {
        die("Error: Invalid or missing location coordinates.");
    }

    $destination_lat = $destination_coordinates['latitude'];
    $destination_lng = $destination_coordinates['longitude'];

    // Fetch the restaurant's location
    $stmt = $pdo->prepare("SELECT latitude, longitude FROM users WHERE id = ? AND role = 'restaurant'");
    $stmt->execute([$order['restaurant_id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($restaurant) {
        $restaurant_lat = $restaurant['latitude'] ?? 0;
        $restaurant_lng = $restaurant['longitude'] ?? 0;
    } else {
        die("Error: Restaurant location not found.");
    }

    // Fetch the delivery user's location
    $stmt = $pdo->prepare("SELECT location_coordinates FROM users WHERE id = (SELECT delivery_user_id FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1)");
    $stmt->execute([$user_id]);
    $delivery_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($delivery_user) {
        $delivery_user_coordinates = json_decode($delivery_user['location_coordinates'], true);
        $delivery_user_lat = $delivery_user_coordinates['latitude'] ?? $restaurant_lat; // Default to restaurant location
        $delivery_user_lng = $delivery_user_coordinates['longitude'] ?? $restaurant_lng; // Default to restaurant location
    } else {
        die("Error: Delivery user not assigned.");
    }

    echo "<pre>";
    print_r($order);
    echo "</pre>";
} catch (PDOException $e) {
    die("Error fetching locations: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Delivery</title>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAHziO-HMFVGmUB3PJE_SGg3oGpznEX6hk" async defer></script>
    <script>
        let deliveryMarker, restaurantMarker, destinationMarker, map;

        function initMap() {
            const restaurantLocation = { lat: <?php echo $restaurant_lat; ?>, lng: <?php echo $restaurant_lng; ?> };
            const destinationLocation = { lat: <?php echo $destination_lat; ?>, lng: <?php echo $destination_lng; ?> };
            const deliveryLocation = { lat: <?php echo $delivery_user_lat; ?>, lng: <?php echo $delivery_user_lng; ?> };

            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 14,
                center: restaurantLocation,
            });

            // Add a marker for the restaurant location
            restaurantMarker = new google.maps.Marker({
                position: restaurantLocation,
                map: map,
                title: "Restaurant Location",
                icon: "http://maps.google.com/mapfiles/ms/icons/red-dot.png",
            });

            // Add a marker for the delivery destination
            destinationMarker = new google.maps.Marker({
                position: destinationLocation,
                map: map,
                title: "Delivery Destination",
                icon: "http://maps.google.com/mapfiles/ms/icons/green-dot.png",
            });

            // Add a marker for the delivery user (initial position will be updated dynamically)
            deliveryMarker = new google.maps.Marker({
                position: deliveryLocation,
                map: map,
                title: "Delivery Location",
                icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png",
            });

            // Start updating the delivery location every 3 seconds
            setInterval(updateDeliveryLocation, 3000);
        }

        function updateDeliveryLocation() {
            // Fetch updated delivery coordinates from the server
            fetch("getDeliveryLocation.php?delivery_user_id=<?php echo $delivery_user_id; ?>")
                .then(response => response.json())
                .then(data => {
                    if (data.lat && data.lng) {
                        const newDeliveryLocation = { lat: data.lat, lng: data.lng };
                        deliveryMarker.setPosition(newDeliveryLocation);
                        map.setCenter(newDeliveryLocation);

                        // Check if the delivery is within 10 meters of the destination
                        const distance = calculateDistance(
                            newDeliveryLocation.lat,
                            newDeliveryLocation.lng,
                            <?php echo $destination_lat; ?>,
                            <?php echo $destination_lng; ?>
                        );

                        if (distance <= 0.01) { // 10 meters in kilometers
                            alert("Order has arrived!");
                        }
                    } else {
                        console.error("Invalid delivery location data:", data);
                    }
                })
                .catch(error => console.error("Error fetching delivery location:", error));
        }

        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371; // Radius of the Earth in kilometers
            const dLat = (lat2 - lat1) * (Math.PI / 180);
            const dLng = (lng2 - lng1) * (Math.PI / 180);
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * (Math.PI / 180)) *
                Math.cos(lat2 * (Math.PI / 180)) *
                Math.sin(dLng / 2) *
                Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c; // Distance in kilometers
        }
    </script>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .side-nav {
            width: 250px;
            background: #333;
            color: #fff;
            height: 100vh;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            position: fixed;
        }

        .side-nav h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 22px;
        }

        .side-nav a {
            display: block;
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 5px;
            transition: 0.3s;
        }

        .side-nav a:hover {
            background: #FF4B3A;
        }

        .container {
            margin-left: 270px;
            padding: 20px;
        }

        #map {
            width: 100%;
            height: 500px;
            border-radius: 16px;
        }
    </style>
</head>
<body>
<div class="side-nav">
    <h2>Menu</h2>
    <a href="homescreen.php">Home</a>
    <a href="orderhistory.php">Order History</a>
    <a href="profilescreen.php">Profile</a>
    <a href="ratingscreen.php">Rate Us</a>
    <a href="TrackDeliveryScreen.php">Track Delivery</a>
</div>

 <div class="container">
    <h1>Track Delivery</h1>
    <div id="map"></div>
</div>
</body>
</html>