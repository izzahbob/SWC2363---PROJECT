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

// Check if the cart session exists
if (!isset($_SESSION['cart_items']) || empty($_SESSION['cart_items'])) {
    header("Location: cart.php");
    exit;
}

$cart_items = $_SESSION['cart_items'];
$subtotal = $_SESSION['total_price'];
$tax_rate = 0.06;
$shipping_cost = 10;
$taxes = $subtotal * $tax_rate;
$total_price = $subtotal + $taxes + $shipping_cost;

// If the "Place Order" button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $user_id = $_SESSION['user_id'] ?? 1;
    $order_date = date('Y-m-d H:i:s');
    $error_message = '';

    // Validate form data
    $required_fields = [
        'full_name' => 'Full Name',
        'email' => 'Email',
        'phone' => 'Phone Number',
        'address' => 'Address',
        'city' => 'City',
        'state' => 'State',
        'zipcode' => 'ZIP Code',
        'card_number' => 'Card Number',
        'card_expiry' => 'Card Expiry Date',
        'card_cvv' => 'CVV'
    ];

    $form_errors = [];
    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $form_errors[] = "$label is required";
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $form_errors[] = "Invalid email format";
    }

    // Validate phone number (basic format: XXX-XXX-XXXX)
    if (!preg_match("/^\d{3}-\d{3}-\d{4}$/", $_POST['phone'])) {
        $form_errors[] = "Phone number must be in format: XXX-XXX-XXXX";
    }

    // Validate card number (basic check for 16 digits)
    if (!preg_match("/^\d{16}$/", str_replace(' ', '', $_POST['card_number']))) {
        $form_errors[] = "Invalid card number (16 digits).";
    }

    // Validate expiry date (MM/YY format)
    if (!preg_match("/^(0[1-9]|1[0-2])\/([0-9]{2})$/", $_POST['card_expiry'])) {
        $form_errors[] = "Expiry date must be in MM/YY format";
    }

    // Validate CVV (3 or 4 digits)
    if (!preg_match("/^\d{3,4}$/", $_POST['card_cvv'])) {
        $form_errors[] = "Invalid CVV";
    }

    if (empty($form_errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert order details into the orders table with shipping information
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, taxes, shipping, order_date, full_name, email, phone, address, city, state, zipcode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Error preparing order statement: " . $conn->error);
            }
            
            $stmt->bind_param('iddsssssssss', 
                $user_id, 
                $total_price, 
                $taxes, 
                $shipping_cost, 
                $order_date,
                $_POST['full_name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'],
                $_POST['city'],
                $_POST['state'],
                $_POST['zipcode']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error inserting order: " . $stmt->error);
            }
            
            $order_id = $stmt->insert_id;

            // Insert each item into the order_item table
            foreach ($cart_items as $item) {
                $product_price = $item['product_price'];
                $quantity = $item['quantity'];
                
                // Get product_id if not already set
                if (!isset($item['product_id'])) {
                    $product_query = $conn->prepare("SELECT id FROM products WHERE name = ?");
                    if (!$product_query) {
                        throw new Exception("Error preparing product query: " . $conn->error);
                    }
                    
                    $product_query->bind_param('s', $item['product_name']);
                    if (!$product_query->execute()) {
                        throw new Exception("Error fetching product ID: " . $product_query->error);
                    }
                    
                    $product_result = $product_query->get_result();
                    $product_data = $product_result->fetch_assoc();
                    
                    if (!$product_data) {
                        throw new Exception("Product not found: " . $item['product_name']);
                    }
                    
                    $product_id = $product_data['id'];
                } else {
                    $product_id = $item['product_id'];
                }

                // Insert order item
                $stmt = $conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Error preparing order item statement: " . $conn->error);
                }
                
                $stmt->bind_param('iiid', $order_id, $product_id, $quantity, $product_price);
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting order item: " . $stmt->error);
                }
            }

            // Clear the cart in database
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception("Error preparing cart cleanup statement: " . $conn->error);
            }
            
            $stmt->bind_param('i', $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Error clearing cart: " . $stmt->error);
            }

            // If we get here, everything worked! Commit the transaction
            $conn->commit();

            // Clear the cart session
            unset($_SESSION['cart_items']);
            unset($_SESSION['total_price']);

            // Success message and redirect
            echo "<script>alert('Order placed successfully! Your order ID is $order_id.');</script>";
            echo "<script>window.location.href = 'index5.php';</script>";
            exit;

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Could not place the order: " . $e->getMessage();
        }
    } else {
        $error_message = "Please correct the following errors:<br>" . implode("<br>", $form_errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .checkout-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .checkout-table th, .checkout-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .section-title {
            margin-top: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .price-summary {
            text-align: right;
            margin-top: 20px;
        }
        .place-order-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            float: right;
            margin-top: 20px;
        }
        .place-order-btn:hover {
            background-color: #45a049;
        }
        .error-message {
            color: red;
            padding: 10px;
            margin: 10px 0;
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
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

    <div class="checkout-container">
        <h1>Checkout</h1>
        
        <?php if (isset($error_message) && !empty($error_message)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <table class="checkout-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']); ?></td>
                        <td>$<?= number_format($item['product_price'], 2); ?></td>
                        <td><?= (int)$item['quantity']; ?></td>
                        <td>$<?= number_format($item['product_price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="POST" class="checkout-form">
            <h2 class="section-title">Shipping Information</h2>
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone (XXX-XXX-XXXX) *</label>
                    <input type="tel" id="phone" name="phone" pattern="\d{3}-\d{3}-\d{4}" required>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address *</label>
                <input type="text" id="address" name="address" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city">City *</label>
                    <input type="text" id="city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="state">State *</label>
                    <input type="text" id="state" name="state" required>
                </div>
                <div class="form-group">
                    <label for="zipcode">ZIP Code *</label>
                    <input type="text" id="zipcode" name="zipcode" pattern="\d{5}" required>
                </div>
            </div>

            <h2 class="section-title">Payment Information</h2>
            <div class="form-group">
                <label for="card_number">Card Number *</label>
                <input type="text" id="card_number" name="card_number" pattern="\d{16}" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="card_expiry">Expiry Date (MM/YY) *</label>
                    <input type="text" id="card_expiry" name="card_expiry" pattern="(0[1-9]|1[0-2])\/([0-9]{2})" required>
                </div>
                <div class="form-group">
                    <label for="card_cvv">CVV *</label>
                    <input type="text" id="card_cvv" name="card_cvv" pattern="\d{3,4}" required>
                </div>
            </div>

            <div class="price-summary">
                <p>Subtotal: $<?= number_format($subtotal, 2); ?></p>
                <p>Taxes (6%): $<?= number_format($taxes, 2); ?></p>
                <p>Shipping: $<?= number_format($shipping_cost, 2); ?></p>
                <h3>Total: $<?= number_format($total_price, 2); ?></h3>

                <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
            </div>
        </form>
    </div>

    <footer>
        <p>&copy; 2024 Your E-Commerce Store. All rights reserved.</p>
    </footer>
</body>
</html>