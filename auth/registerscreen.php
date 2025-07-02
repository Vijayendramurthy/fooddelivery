<?php
session_start();
include '../config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = trim($_POST['role']);
    $restaurant_name = null;
    $address = null;
    $latitude = null;
    $longitude = null;

    if ($role === 'restaurant') {
        $restaurant_name = trim($_POST['restaurant_name']);
        $address = trim($_POST['address']);
        $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;

        if (empty($restaurant_name) || empty($address) || empty($latitude) || empty($longitude)) {
            $errors[] = "Restaurant name, address, latitude, and longitude are required for restaurants.";
        }
    }

    if (empty($name) || empty($phone) || empty($email) || empty($password) || empty($role)) {
        $errors[] = "All fields are required.";
    } else {
        $query = "INSERT INTO users (name, phone_number, email, password, role, restaurant_name, address, latitude, longitude) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssssssssd', $name, $phone, $email, $password, $role, $restaurant_name, $address, $latitude, $longitude);

        if ($stmt->execute()) {
            header('Location: loginscreen.php');
            exit();
        } else {
            $errors[] = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Food Delivery</title>
    <style>
        /* Gradient background */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 90%;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            width: 80px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: 0.3s;
        }

        input:focus, select:focus {
            border-color: #FF4B3A;
            box-shadow: 0 0 8px rgba(255, 75, 58, 0.5);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #FF4B3A, #FF8C42);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: linear-gradient(90deg, #FF8C42, #FF4B3A);
        }

        .error {
            color: #FF4B3A;
            text-align: center;
            margin-bottom: 10px;
        }

        .link {
            text-align: center;
            margin-top: 20px;
        }

        .link a {
            color: #FF4B3A;
            text-decoration: none;
            transition: 0.3s;
        }

        .link a:hover {
            color: #FF8C42;
        }

        #map {
            height: 300px;
            border-radius: 12px;
            margin-top: 10px;
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
<div class="container">
    <div class="logo">
        <img src="https://cdn-icons-png.flaticon.com/512/1046/1046784.png" alt="Food Delivery Logo">
    </div>

    <h2>Register</h2>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone_number" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Role</label>
            <select name="role" id="role" onchange="toggleRestaurantFields()" required>
                <option value="">Select role</option>
                <option value="customer">Customer</option>
                <option value="restaurant">Restaurant</option>
                <option value="delivery">Delivery</option>
            </select>
        </div>

        <div id="restaurant-fields" style="display: none;">
            <div class="form-group">
                <label>Restaurant Name</label>
                <input type="text" name="restaurant_name" id="restaurant_name">
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" id="address">
            </div>

            <div class="form-group">
                <label>Latitude</label>
                <input type="text" name="latitude" id="latitude" readonly>
            </div>

            <div class="form-group">
                <label>Longitude</label>
                <input type="text" name="longitude" id="longitude" readonly>
            </div>

            <div id="map"></div>
        </div>

        <button type="submit" class="btn">Register</button>
    </form>

    <div class="link">
        <p>Already have an account? <a href="loginscreen.php">Login</a></p>
    </div>
</div>

<script>
    function toggleRestaurantFields() {
        const role = document.getElementById('role').value;
        const restaurantFields = document.getElementById('restaurant-fields');
        if (role === 'restaurant') {
            restaurantFields.style.display = 'block';
            setTimeout(initMap, 100); // Small delay to ensure element is shown
        } else {
            restaurantFields.style.display = 'none';
        }
    }

    function initMap() {
        // Prevent map re-initialization
        if (window.mapInitialized) return;
        window.mapInitialized = true;

        const bhimavaramCoords = [16.5449, 81.5212];
        const map = L.map('map').setView(bhimavaramCoords, 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        let marker = null;

        map.on('click', function(e) {
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker(e.latlng).addTo(map);
            document.getElementById('latitude').value = e.latlng.lat.toFixed(6);
            document.getElementById('longitude').value = e.latlng.lng.toFixed(6);
        });
    }
</script>
</body>
</html>
