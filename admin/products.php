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
if (!$ipWhitelist->checkAndLogAccess(null, 'admin_products')) {
    http_response_code(403);
    die('Access Denied: Your IP address is not whitelisted.');
}

// Handle product actions
if ($_GET['action'] == 'delete') {
    $id = $_GET['id'];
    $conn->query("DELETE FROM products WHERE id=$id");
    header("Location: products.php");
}

// Get all products
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products — Euodia Admin</title>
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

        .content-section {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
        }

        .section-title {
            color: #d4af37;
            font-size: 1.5em;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .btn-add {
            background: #d4af37;
            color: #000;
            padding: 12px 20px;
            margin-bottom: 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-add:hover {
            opacity: 0.8;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #eee;
        }

        table th {
            background: #0a0a0a;
            color: #d4af37;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #333;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #333;
        }

        table tr:hover {
            background: #222;
        }

        .action-btn {
            padding: 6px 12px;
            margin: 0 4px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            font-size: 0.85em;
        }

        .btn-edit {
            background: #d4af37;
            color: #000;
        }

        .btn-delete {
            background: #c41e3a;
            color: #fff;
        }

        .product-image {
            max-width: 80px;
            max-height: 80px;
            border-radius: 4px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
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
                <h1>Manage Products</h1>
            </div>

            <div class="content-section">
                <h2 class="section-title">Products List</h2>
                <a href="add_product.php" class="btn-add">+ Add New Product</a>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products->num_rows > 0): ?>
                                <?php while ($product = $products->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if ($product['image']): ?>
                                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                            <?php else: ?>
                                                No image
                                            <?php endif; ?>
                                        </td>
                                        <td>#<?php echo $product['id']; ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($product['price'], 0); ?> XAF</td>
                                        <td><?php echo substr(htmlspecialchars($product['description'] ?? ''), 0, 50); ?>...</td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="action-btn btn-edit">Edit</a>
                                            <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this product?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-state">No products found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
