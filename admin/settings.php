<?php
include "../includes/db.php";
include "../includes/jwt.php";
include "../includes/ipwhitelist.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Initialize IP Whitelist handler early for IP check
$ipWhitelist = new IPWhitelist($conn);

// Check IP whitelist
if (!$ipWhitelist->checkAndLogAccess(null, 'admin_settings')) {
    http_response_code(403);
    die('Access Denied: Your IP address is not whitelisted.');
}

// Handle settings update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $success_message = "Settings updated successfully!";
    }
    
    // Handle IP whitelist actions
    if (isset($_POST['add_ip'])) {
        $ip = trim($_POST['ip_address'] ?? '');
        $description = trim($_POST['ip_description'] ?? '');
        
        if (empty($ip)) {
            $error_message = "IP address cannot be empty";
        } else {
            $result = $ipWhitelist->addIP($ip, $description);
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
        }
    } elseif (isset($_POST['remove_ip'])) {
        $id = intval($_POST['ip_id'] ?? 0);
        $result = $ipWhitelist->removeIP($id);
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    } elseif (isset($_POST['toggle_ip_status'])) {
        $id = intval($_POST['ip_id'] ?? 0);
        $result = $ipWhitelist->toggleIPStatus($id);
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    } elseif (isset($_POST['toggle_whitelist'])) {
        $enabled = isset($_POST['whitelist_enabled']);
        if ($ipWhitelist->setWhitelistEnabled($enabled)) {
            $success_message = "Whitelist " . ($enabled ? "enabled" : "disabled") . " successfully!";
        } else {
            $error_message = "Failed to update whitelist status";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — Euodia Admin</title>
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

        .form-card {
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            max-width: 600px;
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

        .info-box {
            background: #0a0a0a;
            border-left: 3px solid #d4af37;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .info-box p {
            margin: 5px 0;
            color: #eee;
        }

        .info-box strong {
            color: #d4af37;
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
                <li><a href="categories.php">🏷️ Categories</a></li>
                <li><a href="settings.php" class="active">⚙️ Settings</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <h1>Settings</h1>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="content-section">
                <h2 class="section-title">System Information</h2>
                
                <div class="info-box">
                    <p><strong>Store Name:</strong> Euodia</p>
                    <p><strong>Installed Version:</strong> 1.0</p>
                    <p><strong>Server:</strong> <?php echo php_uname(); ?></p>
                    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                </div>
            </div>

            <div class="content-section">
                <h2 class="section-title">Admin Settings</h2>
                
                <div class="form-card">
                    <form method="POST">
                        <div class="form-group">
                            <label for="store_email">Store Email</label>
                            <input type="email" id="store_email" name="store_email" placeholder="admin@euodia.com" value="admin@euodia.com">
                        </div>

                        <div class="form-group">
                            <label for="store_phone">Store Phone</label>
                            <input type="tel" id="store_phone" name="store_phone" placeholder="+237 XXX XXX XXX">
                        </div>

                        <div class="form-group">
                            <label for="store_description">Store Description</label>
                            <textarea id="store_description" name="store_description" rows="4" placeholder="Tell customers about your store...">Premium perfumes and luxury fragrances</textarea>
                        </div>

                        <button type="submit" name="update_settings" class="btn">Save Settings</button>
                    </form>
                </div>
            </div>

            <div class="content-section">
                <h2 class="section-title">IP Whitelist Management</h2>
                
                <div class="form-card">
                    <h3 style="color: #d4af37; margin-top: 0;">Enable/Disable Whitelist</h3>
                    <form method="POST" style="display: inline;">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="whitelist_enabled" <?php echo $ipWhitelist->isWhitelistEnabled() ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                                <span style="color: #eee;">Enforce IP Whitelist</span>
                            </label>
                            <p style="color: #aaa; font-size: 0.9em; margin: 10px 0 0 0;">When enabled, only whitelisted IPs can access admin panel</p>
                        </div>
                        <button type="submit" name="toggle_whitelist" class="btn">Update</button>
                    </form>
                </div>

                <div class="form-card">
                    <h3 style="color: #d4af37; margin-top: 0;">Add New IP Address</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="ip_address">IP Address</label>
                            <input type="text" id="ip_address" name="ip_address" placeholder="192.168.1.100 or 10.0.0.0/8" required>
                        </div>

                        <div class="form-group">
                            <label for="ip_description">Description (Optional)</label>
                            <input type="text" id="ip_description" name="ip_description" placeholder="e.g., Office Network, Home IP">
                        </div>

                        <button type="submit" name="add_ip" class="btn">Add to Whitelist</button>
                    </form>
                </div>

                <div class="form-card">
                    <h3 style="color: #d4af37; margin-top: 0;">Whitelisted IP Addresses</h3>
                    <?php
                    $ips_result = $ipWhitelist->getWhitelistedIPs();
                    if ($ips_result && $ips_result->num_rows > 0):
                    ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #333;">
                                <th style="padding: 10px; text-align: left; color: #d4af37;">IP Address</th>
                                <th style="padding: 10px; text-align: left; color: #d4af37;">Description</th>
                                <th style="padding: 10px; text-align: left; color: #d4af37;">Status</th>
                                <th style="padding: 10px; text-align: center; color: #d4af37;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ip_row = $ips_result->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid #333;">
                                <td style="padding: 10px; color: #eee; font-family: monospace;"><?php echo htmlspecialchars($ip_row['ip_address']); ?></td>
                                <td style="padding: 10px; color: #aaa;"><?php echo htmlspecialchars($ip_row['description'] ?? 'N/A'); ?></td>
                                <td style="padding: 10px;">
                                    <span style="padding: 5px 10px; border-radius: 4px; background: <?php echo $ip_row['status'] === 'active' ? '#28a745' : '#dc3545'; ?>; color: white; font-size: 0.85em;">
                                        <?php echo ucfirst($ip_row['status']); ?>
                                    </span>
                                </td>
                                <td style="padding: 10px; text-align: center;">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="ip_id" value="<?php echo $ip_row['id']; ?>">
                                        <button type="submit" name="toggle_ip_status" class="btn" style="padding: 5px 10px; font-size: 0.85em; background: #ffc107; color: #000; margin-right: 5px;">
                                            Toggle
                                        </button>
                                        <button type="submit" name="remove_ip" class="btn" style="padding: 5px 10px; font-size: 0.85em; background: #dc3545; color: white;" onclick="return confirm('Remove this IP?');">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="color: #aaa; text-align: center; padding: 20px;">No IP addresses whitelisted yet</p>
                    <?php endif; ?>
                </div>

                <div class="info-box">
                    <p><strong>Current IP:</strong> <code style="color: #d4af37; font-family: monospace;"><?php echo htmlspecialchars(\IPWhitelist::getClientIP()); ?></code></p>
                    <p style="color: #aaa; font-size: 0.9em;">Your current IP address. Add this to the whitelist to ensure you don't get locked out.</p>
                </div>
            </div>
                
                <div class="info-box">
                    <p>For technical support, please contact:</p>
                    <p><strong>Email:</strong> support@euodia.com</p>
                    <p><strong>Documentation:</strong> <a href="#" style="color: #d4af37;">View Admin Guide</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
