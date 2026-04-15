<?php
include "../includes/db.php";
include "../includes/jwt.php";
include "../includes/ipwhitelist.php";

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Check IP whitelist
$ipWhitelist = new IPWhitelist($conn);
if (!$ipWhitelist->checkAndLogAccess(null, 'admin_dashboard')) {
    http_response_code(403);
    die('Access Denied: Your IP address is not whitelisted.');
}

// For now, allow all logged-in users to access admin (you can add role checking later)
// TODO: Add admin role verification

$stats = [
    'total_products' => $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
    'total_orders' => $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'],
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(total) as sum FROM orders")->fetch_assoc()['sum'] ?? 0
];

// Get recent orders
$recent_orders = $conn->query("SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC LIMIT 10");
$recent_products = $conn->query("SELECT * FROM products ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Euodia Scents</title>
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #eee;
        }

        .user-info a {
            color: #d4af37;
            text-decoration: none;
            font-weight: bold;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stat-card h3 {
            color: #999;
            margin: 0 0 10px 0;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .value {
            color: #d4af37;
            font-size: 2.5em;
            font-weight: bold;
            margin: 0;
        }

        .content-section {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .section-title {
            color: #d4af37;
            font-size: 1.5em;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
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

        .btn-view {
            background: #333;
            color: #d4af37;
        }

        .btn-add {
            background: #d4af37;
            color: #000;
            padding: 12px 20px;
            margin-bottom: 15px;
        }

        .btn-add:hover,
        .action-btn:hover {
            opacity: 0.8;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-bar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">ADMIN</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="products.php">📦 Products</a></li>
                <li><a href="orders.php">📋 Orders</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="categories.php">🏷️ Categories</a></li>
                <li><a href="settings.php">⚙️ Settings</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user']['name']); ?></strong></span>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <p class="value"><?php echo $stats['total_products']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p class="value"><?php echo $stats['total_orders']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p class="value"><?php echo $stats['total_users']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <p class="value"><?php echo number_format($stats['total_revenue'], 0); ?> XAF</p>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="content-section">
                <h2 class="section-title">Recent Orders</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_orders->num_rows > 0): ?>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                                        <td><?php echo number_format($order['total'], 0); ?> XAF</td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'] ?? 'now')); ?></td>
                                        <td>
                                            <a href="orders.php?id=<?php echo $order['id']; ?>" class="action-btn btn-view">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">No orders yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Products -->
            <div class="content-section">
                <h2 class="section-title">Recent Products</h2>
                <a href="products.php?action=add" class="btn-add">+ Add New Product</a>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_products->num_rows > 0): ?>
                                <?php while ($product = $recent_products->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $product['id']; ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($product['price'], 0); ?> XAF</td>
                                        <td>
                                            <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="action-btn btn-edit">Edit</a>
                                            <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this product?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="empty-state">No products yet</td>
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
