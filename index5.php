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

// Temporary setup for testing (remove when login system is ready)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
    
}

$user_id = $_SESSION['user_id'];

// Handle adding to favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_favorites'])) {
    $product_id = intval($_POST['product_id']);

    // Check if product already exists in favorites
    $check_query = "SELECT * FROM favorites WHERE user_id = $user_id AND product_id = $product_id";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows === 0) {
        // Insert into favorites
        $insert_query = "INSERT INTO favorites (user_id, product_id) VALUES ($user_id, $product_id)";
        $conn->query($insert_query);
    }

    header("Location: favorites.php"); // Redirect to favorites page after adding to favorites
    exit;
}

// Fetch products
$product_query = "SELECT * FROM products";
$product_result = $conn->query($product_query);

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = 1; // Default quantity

    // Check if product already exists in cart
    $check_query = "SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        // Update quantity
        $update_query = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $product_id";
        $conn->query($update_query);
    } else {
        // Insert into cart
        $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)";
        $conn->query($insert_query);
    }

    header("Location: cart.php"); // Redirect to cart page after adding to cart
    exit;
}

// Fetch products
$product_query = "SELECT * FROM products";
$product_result = $conn->query($product_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link rel="stylesheet" href="style.css">
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

    <main>
        <h1>Welcome to Our E-Commerce Store</h1>
        <div class="products">
            <?php while ($product = $product_result->fetch_assoc()): ?>
                <div class="product">
                    <img src="<?php echo $product['mainImg']; ?>" alt="<?php echo $product['name']; ?>">
                    <h2><?php echo $product['name']; ?></h2>
                    <p><?php echo $product['description']; ?></p>
                    <p>Price: RM<?php echo $product['price']; ?></p>
                    <form method="POST" action="">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" name="add_to_favorites">Add to Favorites</button>
                        <button type="submit" name="add_to_cart">Add to Cart</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Your E-Commerce Store. All rights reserved.</p>
    </footer>
</body>
</html>
