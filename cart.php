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

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}

$user_id = $_SESSION['user_id'];

// Handle delete item request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    $cart_id = intval($_POST['cart_id']);
    $conn->query("DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id");
    header('Location: cart.php');
    exit;
}

// Retrieve products from the session
$favorites_products = isset($_SESSION['favorites_products']) ? $_SESSION['favorites_products'] : [];


// Retrieve cart items by joining with the products table
$query = "
    SELECT cart.id AS cart_id, products.name AS product_name, products.price AS product_price, cart.quantity
    FROM cart
    INNER JOIN products ON cart.product_id = products.id
    WHERE cart.user_id = $user_id
";
$result = $conn->query($query);

$cart_items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total += $row['product_price'] * $row['quantity'];
}

// Save the cart data to session for use in checkout.php
$_SESSION['cart_items'] = $cart_items;
$_SESSION['total_price'] = $total;

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .cart-container {
            max-width: 500px;
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: center;
        }
        .cart-item img {
            max-width: 50px;
            margin-right: 10px;
        }
        .cart-item-details {
            flex: 1;
        }
        .cart-total {
            font-size: 1.2em;
            font-weight: bold;
            text-align: right;
            margin-top: 15px;
        }
        .checkout-button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .delete-button {
            color: red;
            cursor: pointer;
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

    <div class="cart-container">
        <h2>Your Cart</h2>

        <?php if (!empty($cart_items)): ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-details">
                        <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                        <p>$<?= number_format($item['product_price'], 2) ?> x <?= intval($item['quantity']) ?></p>
                    </div>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="cart_id" value="<?= intval($item['cart_id']) ?>">
                        <button type="submit" name="delete_item" class="delete-button">✖</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <div class="cart-total">
                Total: $<?= number_format($total, 2) ?>
            </div>
            <form action="checkout.php" method="GET">
                <button type="submit" class="checkout-button">Proceed to Checkout</button>
            </form>
        <?php else: ?>
            <p>Your cart is empty.</p>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 Your E-Commerce Store. All rights reserved.</p>
    </footer>
</body>
</html>
