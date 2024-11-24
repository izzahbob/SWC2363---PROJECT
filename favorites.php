<?php 
session_start();

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'shuoizz_store';

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Temporary user setup for testing (remove when login system is ready)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Replace with a valid user ID from your users table
}

$user_id = $_SESSION['user_id'];

// Handle removal from favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $product_id = intval($_POST['remove_item']);
    $delete_query = "DELETE FROM favorites WHERE user_id = $user_id AND product_id = $product_id";
    $conn->query($delete_query);
    header('Location: favorites.php'); // Refresh the favorites page after deletion
    exit;
}

// Handle add to cart and redirect to cart.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['add_to_cart']);

    // Check if the product is already in the cart
    $check_cart_query = "SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id";
    $cart_result = $conn->query($check_cart_query);

    if ($cart_result->num_rows > 0) {
        // If it exists in the cart, increment the quantity
        $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $product_id");
    } else {
        // Otherwise, add the product to the cart with quantity = 1
        $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, 1)");
    }

    // Redirect to cart.php
    header('Location: cart.php');
    exit;
}

// Fetch user's favorite items
$favorites_query = "
    SELECT products.id, products.name, products.mainImg, products.price 
    FROM products 
    JOIN favorites ON products.id = favorites.product_id 
    WHERE favorites.user_id = $user_id";
$favorites_result = $conn->query($favorites_query);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Favorites</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .favorites-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 90%;
            padding: 20px;
        }

        .favorites-container h1 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .favorite-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f7f7f7;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 10px;
        }

        .favorite-item:last-child {
            margin-bottom: 0;
        }

        .favorite-item span {
            font-size: 1rem;
            font-weight: 500;
            color: #333;
        }

        .remove-btn {
            background-color: #ff4d4f;
            border: none;
            color: white;
            font-size: 0.875rem;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .remove-btn:hover {
            background-color: #e04343;
        }

        .add-to-cart-btn {
            background-color: #28a745;
            border: none;
            color: white;
            font-size: 0.875rem;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .add-to-cart-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
<nav>
    <ul>
        <li><a href="index5.php">Home</a></li>
        <li><a href="favorites.php">Favorites</a></li>
        <li><a href="cart.php">Cart</a></li>
        <li><a href="account4.php">Account</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<div class="favorites-container">
    <h1>Your Favorites</h1>
    <?php if ($favorites_result->num_rows > 0): ?>
        <?php while ($favorite = $favorites_result->fetch_assoc()): ?>
            <div class="favorite-item">
                <img src="<?php echo htmlspecialchars($favorite['mainImg']); ?>" alt="<?php echo htmlspecialchars($favorite['name']); ?>" width="50">
                <div class="favorite-details">
                    <span><?php echo htmlspecialchars($favorite['name']); ?></span>
                    <span class="price">RM<?php echo htmlspecialchars($favorite['price']); ?></span>
                </div>
                <form method="POST" style="margin: 0; display: inline;">
                    <input type="hidden" name="add_to_cart" value="<?php echo $favorite['id']; ?>">
                    <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                </form>
                <form method="POST" style="margin: 0; display: inline;">
                    <input type="hidden" name="remove_item" value="<?php echo $favorite['id']; ?>">
                    <button type="submit" class="remove-btn">Remove</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; color: #555;">No favorites added yet.</p>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; 2024 Your E-Commerce Store. All rights reserved.</p>
</footer>
</body>
</html>
