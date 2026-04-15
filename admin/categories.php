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
if (!$ipWhitelist->checkAndLogAccess(null, 'admin_categories')) {
    http_response_code(403);
    die('Access Denied: Your IP address is not whitelisted.');
}

// Handle add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';

    if (!empty($name)) {
        $name = $conn->real_escape_string($name);
        $description = $conn->real_escape_string($description);
        $conn->query("INSERT INTO categories (name, description) VALUES ('$name', '$description')");
        $success_message = "Category added successfully!";
    } else {
        $error_message = "Category name is required!";
    }
}

// Handle delete category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id = '$id'");
    header("Location: categories.php");
    exit;
}

// Get all categories
$categories = $conn->query("SELECT id, name, description FROM categories ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories — Euodia Admin</title>
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

        .btn {
            background: #d4af37;
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s ease;
        }

        .btn:hover {
            background: #e5c158;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
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

        .form-card {
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
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
        .form-group textarea {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #333;
            color: #eee;
            border-radius: 4px;
            font-family: Arial, sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 5px rgba(212, 175, 55, 0.3);
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

        .action-buttons {
            display: flex;
            gap: 5px;
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
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">ADMIN</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="products.php">📦 Products</a></li>
                <li><a href="orders.php">📋 Orders</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="categories.php" class="active">🏷️ Categories</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <h1>Manage Categories</h1>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="content-section">
                <h2 class="section-title">Add New Category</h2>
                
                <div class="form-card">
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Category Name</label>
                            <input type="text" id="name" name="name" required placeholder="Enter category name">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" placeholder="Enter category description"></textarea>
                        </div>

                        <button type="submit" name="add_category" class="btn">Add Category</button>
                    </form>
                </div>
            </div>

            <div class="content-section">
                <h2 class="section-title">All Categories</h2>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Category ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($categories->num_rows > 0): ?>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $cat['id']; ?></td>
                                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($cat['description'] ?? '', 0, 50)); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="categories.php?delete=<?php echo $cat['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this category?')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-state">No categories found</td>
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
