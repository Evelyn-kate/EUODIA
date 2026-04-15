<?php
include "../includes/db.php";
include "../includes/jwt.php";
include "../includes/ipwhitelist.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Check IP whitelist
$ipWhitelist = new IPWhitelist($conn);
if (!$ipWhitelist->checkAndLogAccess(null, 'admin_edit_product')) {
    http_response_code(403);
    die('Access Denied: Your IP address is not whitelisted.');
}

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header("Location: products.php");
    exit;
}

// Get product details
$result = $conn->query("SELECT * FROM products WHERE id = '$product_id'");
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: products.php");
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    if (!empty($name) && !empty($price)) {
        $name = $conn->real_escape_string($name);
        $price = floatval($price);
        $category_id = intval($category_id);
        $description = $conn->real_escape_string($description);
        $image_url = $conn->real_escape_string($image_url);

        $conn->query("UPDATE products SET name='$name', price=$price, category_id=$category_id, description='$description', image_url='$image_url' WHERE id='$product_id'");
        $success_message = "Product updated successfully!";
        
        // Refresh product data
        $result = $conn->query("SELECT * FROM products WHERE id = '$product_id'");
        $product = $result->fetch_assoc();
    } else {
        $error_message = "Product name and price are required!";
    }
}

// Get categories
$categories = $conn->query("SELECT id, name FROM categories");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product — Euodia Admin</title>
    <link rel="stylesheet" href="../uploads/style.css">
    <style>
        * { box-sizing: border-box; }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #000;
            border-right: 2px solid #d4af37;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            color: #d4af37;
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 10px 0;
        }

        .sidebar-menu a {
            color: #eee;
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            border-radius: 4px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #1a1a1a;
            color: #d4af37;
            border-left-color: #d4af37;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
            background: #111;
        }

        .top-bar {
            background: #000;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border: 1px solid #333;
        }

        .top-bar h1 {
            margin: 0;
            color: #d4af37;
            font-size: 1.8em;
        }

        .back-link {
            color: #d4af37;
            text-decoration: none;
            font-size: 0.9em;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .content-section {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            max-width: 600px;
        }

        .form-card {
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #d4af37;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #333;
            color: #eee;
            border-radius: 4px;
            font-family: Arial, sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 5px rgba(212, 175, 55, 0.3);
        }

        .form-group option {
            background: #1a1a1a;
            color: #eee;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            background: #d4af37;
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s ease;
            flex: 1;
            text-align: center;
            text-decoration: none;
        }

        .btn:hover {
            background: #e5c158;
        }

        .btn-secondary {
            background: #333;
            color: #eee;
        }

        .btn-secondary:hover {
            background: #444;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .alert-success {
            background: #28a745;
            color: white;
        }

        .alert-danger {
            background: #dc3545;
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 2px solid #d4af37;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">ADMIN</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="products.php" class="active">📦 Products</a></li>
                <li><a href="orders.php">📋 Orders</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="categories.php">🏷️ Categories</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <h1>Edit Product</h1>
                <a href="products.php" class="back-link">← Back to Products</a>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="content-section">
                <div class="form-card">
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="price">Price ($)</label>
                            <input type="number" id="price" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="image_url">Image URL</label>
                            <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($product['image_url']); ?>" placeholder="https://example.com/image.jpg">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="button-group">
                            <button type="submit" class="btn">Update Product</button>
                            <a href="products.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
