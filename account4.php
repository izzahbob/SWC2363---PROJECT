<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login6.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$dbpassword = "";
$dbname = "shuoizz_store";

$conn = new mysqli($servername, $username, $dbpassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize variables
$username = $user['username'];
$email = $user['email'];

// Fetch favorites with product images
$favorites = [];
$favorites_sql = "SELECT p.*, f.id as favorite_id 
                 FROM favorites f 
                 JOIN products p ON f.product_id = p.id 
                 WHERE f.user_id = ?";
$favorites_stmt = $conn->prepare($favorites_sql);
if ($favorites_stmt) {
    $favorites_stmt->bind_param("i", $user_id);
    $favorites_stmt->execute();
    $favorites_result = $favorites_stmt->get_result();
    while ($row = $favorites_result->fetch_assoc()) {
        $favorites[] = $row;
    }
    $favorites_stmt->close();
}

// Fetch cart items with product details
$cart = [];
$cart_sql = "SELECT p.*, c.quantity, c.id as cart_id 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?";
$cart_stmt = $conn->prepare($cart_sql);
if ($cart_stmt) {
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    while ($row = $cart_result->fetch_assoc()) {
        $cart[] = $row;
    }
    $cart_stmt->close();
}

// Updated purchase history query
$purchase_history = [];
$history_sql = "SELECT o.*, oi.*, p.name, p.mainImg 
                FROM orders o 
                JOIN order_item oi ON o.order_id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE o.user_id = ? 
                ORDER BY o.order_date DESC";
$history_stmt = $conn->prepare($history_sql);
if ($history_stmt) {
    $history_stmt->bind_param("i", $user_id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    while ($row = $history_result->fetch_assoc()) {
        $purchase_history[] = $row;
    }
    $history_stmt->close();
}

$stmt->close();
$conn->close();
?>



<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details</title>
    <link href="style.css" rel="stylesheet" type="text/css">
    <style>
        
        body {
            font-family: Arial, sans-serif;
            background-color: #708238;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 700px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #9DC183;
            color: white;
        }
        .avatar {
            margin: 20px auto;
            width: 100px;
            height: 100px;
            background-color: #708238;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            font-weight: bold;
        }
        .user-info {
            text-align: center;
            padding: 10px;
        }
        .user-info h2 {
            margin-bottom: 5px;
            font-size: 1.5em;
        }
        .user-info p {
            color: #708238;
        }
        .sections {
            display: flex;
            border-top: 1px solid #708238;
        }
        .section {
            flex: 1;
            text-align: center;
            padding: 15px;
            cursor: pointer;
            color: #9DC183;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .section:hover,
        .section.active {
            color: #9DC183;
            border-bottom-color: #9DC183;
            font-weight: bold;
        }
        .content {
            padding: 20px;
        }
        .section-content {
            display: none;
        }
        .section-content.active {
            display: block;
        }
        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #708238;
        }
        .cart-total {
            text-align: right;
            font-weight: bold;
            margin-top: 10px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #708238;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background-color: #9DC183;
        }
        .logout-btn {
            background-color: #EF4444;
        }
        .logout-btn:hover {
            background-color: #DC2626;
        }
        .order-header {
        background-color: #f8f9fa;
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
    }
    
    .order-header h4 {
        margin: 0;
        color: #708238;
    }
    
    .order-header p {
        margin: 5px 0;
        color: #666;
    }
    
    hr.my-4 {
        margin: 1rem 0;
        border: 0;
        border-top: 1px solid #708238;
    }
    .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #708238;
            gap: 10px;
        }
        .item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .item-buttons {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <h1>Account Details</h1>
            <div class="header-buttons">
                <button onclick="window.location.href='index5.php'" class="btn">🏠 Home</button>
                <button onclick="logout()" class="btn logout-btn">🚪 Logout</button>
            </div>
        </div>

        <!-- User Info -->
        <div class="avatar"><?php echo isset($username) ? strtoupper(substr($username, 0, 1)) : ''; ?></div>
        <div class="user-info">
            <h2><?php echo htmlspecialchars($username ?? ''); ?></h2>
            <p><?php echo htmlspecialchars($email ?? ''); ?></p>
        </div>

        <!-- Sections -->
        <div class="sections">
            <div class="section active" data-section="favorites">Favorites</div>
            <div class="section" data-section="cart">Cart</div>
            <div class="section" data-section="purchase-history">Purchase History</div>
        </div>

        <!-- Section Content -->
        <div class="content">
             <!-- Favorites Section -->
        <div id="favorites" class="section-content active">
            <h3>Your Favorites</h3>
            <?php if (!empty($favorites)): ?>
                <?php foreach ($favorites as $item): ?>
                    <div class="item">
                        <img src="<?php echo htmlspecialchars($item['mainImg']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                            <span>RM<?php echo number_format($item['price'], 2); ?></span>
                        </div>
                        <div class="item-buttons">
                            <button class="btn" onclick="addToCart(<?php echo $item['id']; ?>)">Add to Cart</button>
                            <button class="btn" onclick="removeFavorite(<?php echo $item['id']; ?>)">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No favorites yet.</p>
            <?php endif; ?>
        </div>

        <!-- Cart Section -->
        <div id="cart" class="section-content">
            <h3>Your Cart</h3>
            <?php
            $cart_total = 0;
            if (!empty($cart)):
                foreach ($cart as $item):
                    $item_total = $item['price'] * $item['quantity'];
                    $cart_total += $item_total;
            ?>
                <div class="item">
                    <img src="<?php echo htmlspecialchars($item['mainImg']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="item-details">
                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                        <span>Qty: <?php echo $item['quantity']; ?></span>
                    </div>
                    <span>RM<?php echo number_format($item_total, 2); ?></span>
                    <button class="btn" onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">Remove</button>
                </div>
            <?php 
                endforeach;
            ?>
                <div class="cart-total">Total: RM<?php echo number_format($cart_total, 2); ?></div>
                <button class="btn" onclick="window.location.href='checkout.php'">Proceed to Checkout</button>
            <?php else: ?>
                <p>Your cart is empty.</p>
            <?php endif; ?>
        </div>

            <!-- Purchase History Section -->
            <div id="purchase-history" class="section-content">
            <h3>Purchase History</h3>
            <?php if (!empty($purchase_history)): ?>
                <?php 
                $current_order_id = null;
                $order_total = 0;
                
                foreach ($purchase_history as $purchase): 
                    if ($current_order_id !== $purchase['order_id']) {
                        if ($current_order_id !== null) {
                            echo '<div class="cart-total">Order Total: RM' . number_format($order_total, 2) . '</div>';
                            echo '<hr class="my-4">';
                        }
                        $current_order_id = $purchase['order_id'];
                        $order_total = $purchase['total_price'];
                        ?>
                        <div class="order-header">
                            <h4>Order #<?php echo htmlspecialchars($purchase['order_id']); ?></h4>
                            <p>Date: <?php echo date('Y-m-d', strtotime($purchase['order_date'])); ?></p>
                            <p>Shipping: RM<?php echo number_format($purchase['shipping'], 2); ?></p>
                            <p>Taxes: RM<?php echo number_format($purchase['taxes'], 2); ?></p>
                        </div>
                    <?php } ?>
                    
                    <div class="item">
                        <img src="<?php echo htmlspecialchars($purchase['mainImg']); ?>" alt="<?php echo htmlspecialchars($purchase['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                        <div class="item-details">
                            <span><?php echo htmlspecialchars($purchase['name']); ?></span>
                            <span>Quantity: <?php echo htmlspecialchars($purchase['quantity']); ?></span>
                        </div>
                        <span>RM<?php echo number_format($purchase['price'], 2); ?></span>
                    </div>
                <?php 
                endforeach;
                if (!empty($purchase_history)) {
                    echo '<div class="cart-total">Order Total: RM' . number_format($order_total, 2) . '</div>';
                }
                ?>
            <?php else: ?>
                <p>No purchase history available.</p>
            <?php endif; ?>
        </div>

    <script>
        // Logout functionality
        function logout() {
            window.location.href = 'logout.php';
        }

        // Section switching functionality
        document.querySelectorAll('.section').forEach(section => {
            section.addEventListener('click', () => {
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                document.querySelectorAll('.section-content').forEach(c => c.classList.remove('active'));
                section.classList.add('active');
                document.getElementById(section.dataset.section).classList.add('active');
            });
        });

        // Remove favorite functionality
        function removeFavorite(id) {
            if (confirm('Are you sure you want to remove this item from favorites?')) {
                fetch('remove_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        }

        function addToCart(productId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ product_id: productId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        function removeFromCart(cartId) {
            if (confirm('Are you sure you want to remove this item from cart?')) {
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ cart_id: cartId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        }
    </script>
</body>
</html>
