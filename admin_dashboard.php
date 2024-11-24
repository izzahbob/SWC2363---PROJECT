<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'shuoizz_store');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        // Add product
        $name = $_POST['name'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $mainImg = $_POST['mainImg'];
        $hoverImg = $_POST['hoverImg'];

        $stmt = $conn->prepare("INSERT INTO products (name, price, description, mainImg, hoverImg) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdsss", $name, $price, $description, $mainImg, $hoverImg);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update_product'])) {
        // Update product
        $id = $_POST['id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $mainImg = $_POST['mainImg'];
        $hoverImg = $_POST['hoverImg'];

        // Ensure all product fields are updated
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ?, mainImg = ?, hoverImg = ? WHERE id = ?");
        $stmt->bind_param("sdsssi", $name, $price, $description, $mainImg, $hoverImg, $id);
        
        if ($stmt->execute()) {
            // Optional: Add success message
            $_SESSION['message'] = "Product updated successfully!";
        } else {
            // Optional: Add error message
            $_SESSION['error'] = "Failed to update product.";
        }
        $stmt->close();
        
        // Redirect to prevent form resubmission
        header('Location: admin_dashboard.php');
        exit();
    } elseif (isset($_POST['delete_product'])) {
        // Delete product
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Search functionality
$search_term = '';
$products = [];

if (isset($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    
    // Search query with prepared statement
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ?");
    $search_param = "%$search_term%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Fetch all products if no search is performed
    $result = $conn->query("SELECT * FROM products");
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();
}


// Fetch products
$result = $conn->query("SELECT * FROM products");
$products = $result->fetch_all(MYSQLI_ASSOC);
$result->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100" x-data="{ 
    modal: false, 
    product: {
        id: '',
        name: '',
        price: '',
        description: '',
        mainImg: '',
        hoverImg: ''
    } 
}">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">Product Management</h1>
        <form method="POST" class="inline">
                <button 
                    type="submit" 
                    name="logout" 
                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition"
                >
                    Logout
                </button>
            </form>

        <?php 
        // Display success or error messages
        if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['message']); 
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['error']); 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Add New Product</h2>
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Product Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            placeholder="Enter product name" 
                            required 
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Price</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="price" 
                            placeholder="Enter price" 
                            required 
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Main Image URL</label>
                    <input 
                        type="text" 
                        name="mainImg" 
                        placeholder="Enter main image URL" 
                        required 
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Hover Image URL</label>
                    <input 
                        type="text" 
                        name="hoverImg" 
                        placeholder="Enter hover image URL" 
                        required 
                        class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                    <textarea 
                        name="description" 
                        placeholder="Enter product description" 
                        required 
                        class="w-full h-24 px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    ></textarea>
                </div>
                
                <button 
                    type="submit" 
                    name="add_product" 
                    class="w-full bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600 transition"
                >
                    Add Product
                </button>
            </form>
        </div>

        <!-- Search Form -->
        <div class="mb-6">
            <form method="GET" class="flex">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search products..." 
                    value="<?php echo htmlspecialchars($search_term); ?>"
                    class="flex-grow px-3 py-2 border rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button 
                    type="submit" 
                    class="bg-blue-500 text-white px-4 py-2 rounded-r-md hover:bg-blue-600 transition"
                >
                    Search
                </button>
            </form>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Price</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3"><?php echo htmlspecialchars($product['id']); ?></td>
                        <td class="p-3"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td class="p-3">$<?php echo number_format($product['price'], 2); ?></td>
                        <td class="p-3">
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="delete_product" class="bg-red-500 text-white px-3 py-1 rounded mr-2">Delete</button>
                                <button 
                                    type="button" 
                                    @click="
                                        modal = true; 
                                        product.id = '<?php echo $product['id']; ?>';
                                        product.name = '<?php echo htmlspecialchars($product['name']); ?>';
                                        product.price = '<?php echo $product['price']; ?>';
                                        product.description = '<?php echo htmlspecialchars($product['description']); ?>';
                                        product.mainImg = '<?php echo htmlspecialchars($product['mainImg']); ?>';
                                        product.hoverImg = '<?php echo htmlspecialchars($product['hoverImg']); ?>'
                                    " 
                                    class="bg-gray-200 text-gray-700 px-3 py-1 rounded"
                                >
                                    Edit
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Edit Modal -->
        <div x-show="modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white p-6 rounded-lg w-1/2" @click.outside="modal = false">
                <h2 class="text-xl font-semibold mb-4">Edit Product</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="id" x-model="product.id">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Product Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            x-model="product.name" 
                            placeholder="Product Name" 
                            required 
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Price</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="price" 
                            x-model="product.price" 
                            placeholder="Price" 
                            required 
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Main Image URL</label>
                        <input 
                            type="text" 
                            name="mainImg" 
                            x-model="product.mainImg" 
                            placeholder="Main Image URL" 
                            required 
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Hover Image URL</label>
                        <input 
                            type="text" 
                            name="hoverImg" 
                            x-model="product.hoverImg" 
                            placeholder="Hover Image URL" 
                            required 
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                        <textarea 
                            name="description" 
                            x-model="product.description" 
                            placeholder="Description" 
                            required 
                            class="w-full h-24 px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <button 
                            type="submit" 
                            name="update_product" 
                            class="bg-blue-500 text-white py-2 rounded-md hover:bg-blue-600 transition"
                        >
                            Update Product
                        </button>
                        <button 
                            type="button" 
                            @click="modal = false" 
                            class="bg-gray-200 text-gray-700 py-2 rounded-md hover:bg-gray-300 transition"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>