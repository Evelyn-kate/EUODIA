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
if (!$ipWhitelist->checkAndLogAccess(null, 'admin_parcels')) {
    http_response_code(403);
    die('Access Denied: Your IP address is not whitelisted.');
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_status') {
        $shipment_id = intval($_POST['shipment_id']);
        $new_status = $conn->real_escape_string($_POST['status']);
        $notes = $conn->real_escape_string($_POST['notes'] ?? '');
        
        $allowed_statuses = ['pending', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'returned', 'lost'];
        if (in_array($new_status, $allowed_statuses)) {
            $conn->query("UPDATE shipments SET status='$new_status', notes='$notes' WHERE id=$shipment_id");
            
            if ($new_status == 'shipped') {
                $conn->query("UPDATE shipments SET shipped_date=NOW() WHERE id=$shipment_id");
            } elseif ($new_status == 'delivered') {
                $conn->query("UPDATE shipments SET delivered_date=NOW() WHERE id=$shipment_id");
            }
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$country_filter = $_GET['country'] ?? '';

// Build query with filters
$where_clause = "1=1";
if ($status_filter) {
    $status_filter = $conn->real_escape_string($status_filter);
    $where_clause .= " AND s.status='$status_filter'";
}
if ($country_filter) {
    $country_filter = $conn->real_escape_string($country_filter);
    $where_clause .= " AND s.country_id=$country_filter";
}

// Get all shipments with order and user details
$shipments = $conn->query("
    SELECT s.*, o.user_id, o.total, o.grand_total, u.name, u.email, c.country_name, c.currency_symbol
    FROM shipments s
    JOIN orders o ON s.order_id = o.id
    JOIN users u ON o.user_id = u.id
    JOIN shipping_countries c ON s.country_id = c.id
    WHERE $where_clause
    ORDER BY s.created_at DESC
");

// Get all countries for filter
$all_countries = $conn->query("SELECT id, country_name FROM shipping_countries WHERE is_active=1 ORDER BY country_name");

// Get status statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status='shipped' THEN 1 ELSE 0 END) as shipped,
        SUM(CASE WHEN status='in_transit' THEN 1 ELSE 0 END) as in_transit,
        SUM(CASE WHEN status='out_for_delivery' THEN 1 ELSE 0 END) as out_for_delivery,
        SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN status='returned' THEN 1 ELSE 0 END) as returned,
        SUM(CASE WHEN status='lost' THEN 1 ELSE 0 END) as lost
    FROM shipments
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcel Tracking — Euodia Admin</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
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
            margin: 0 0 10px 0;
            color: #999;
            font-size: 0.9em;
            text-transform: uppercase;
        }

        .stat-card .value {
            color: #d4af37;
            font-size: 2em;
            font-weight: bold;
        }

        .filters {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            color: #d4af37;
            font-weight: bold;
            font-size: 0.9em;
        }

        .filter-group select {
            background: #111;
            border: 1px solid #333;
            border-radius: 4px;
            color: #eee;
            padding: 8px 12px;
            cursor: pointer;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #d4af37;
        }

        .btn-filter {
            background: #d4af37;
            color: #000;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: opacity 0.3s;
        }

        .btn-filter:hover {
            opacity: 0.8;
        }

        .btn-clear {
            background: #666;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            text-align: center;
        }

        .status-pending { background: #3a2a0a; color: #ffd700; }
        .status-shipped { background: #0a2a3a; color: #87ceeb; }
        .status-in_transit { background: #1a3a0a; color: #90ee90; }
        .status-out_for_delivery { background: #3a1a0a; color: #ffa500; }
        .status-delivered { background: #0a3a0a; color: #00ff00; }
        .status-returned { background: #3a0a1a; color: #ff6b6b; }
        .status-lost { background: #2a0a1a; color: #ff1744; }

        .action-btn {
            padding: 6px 12px;
            margin: 2px;
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
        }

        .modal-content {
            background: #1a1a1a;
            margin: 10% auto;
            padding: 30px;
            border: 1px solid #333;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            color: #eee;
        }

        .modal-close {
            color: #999;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-close:hover {
            color: #d4af37;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            background: #111;
            border: 1px solid #333;
            border-radius: 4px;
            color: #eee;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-save {
            background: #d4af37;
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
        }

        .btn-save:hover {
            opacity: 0.8;
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
                <li><a href="products.php">📦 Products</a></li>
                <li><a href="orders.php">📋 Orders</a></li>
                <li><a href="parcels.php" class="active">🌍 Parcel Tracking</a></li>
                <li><a href="categories.php">🏷️ Categories</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <h1>🌍 International Parcel Tracking</h1>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Parcels</h3>
                    <div class="value"><?php echo $stats['total'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Pending</h3>
                    <div class="value" style="color: #ffd700;"><?php echo $stats['pending'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Shipped</h3>
                    <div class="value" style="color: #87ceeb;"><?php echo $stats['shipped'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <h3>In Transit</h3>
                    <div class="value" style="color: #90ee90;"><?php echo $stats['in_transit'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Out for Delivery</h3>
                    <div class="value" style="color: #ffa500;"><?php echo $stats['out_for_delivery'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Delivered</h3>
                    <div class="value" style="color: #00ff00;"><?php echo $stats['delivered'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Issues</h3>
                    <div class="value" style="color: #ff6b6b;"><?php echo ($stats['returned'] ?? 0) + ($stats['lost'] ?? 0); ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; width: 100%;">
                    <div class="filter-group">
                        <label for="status">Filter by Status:</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo ($status_filter == 'pending' ? 'selected' : ''); ?>>Pending</option>
                            <option value="shipped" <?php echo ($status_filter == 'shipped' ? 'selected' : ''); ?>>Shipped</option>
                            <option value="in_transit" <?php echo ($status_filter == 'in_transit' ? 'selected' : ''); ?>>In Transit</option>
                            <option value="out_for_delivery" <?php echo ($status_filter == 'out_for_delivery' ? 'selected' : ''); ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo ($status_filter == 'delivered' ? 'selected' : ''); ?>>Delivered</option>
                            <option value="returned" <?php echo ($status_filter == 'returned' ? 'selected' : ''); ?>>Returned</option>
                            <option value="lost" <?php echo ($status_filter == 'lost' ? 'selected' : ''); ?>>Lost</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="country">Filter by Country:</label>
                        <select name="country" id="country">
                            <option value="">All Countries</option>
                            <?php while($country = $all_countries->fetch_assoc()): ?>
                                <option value="<?php echo $country['id']; ?>" <?php echo ($country_filter == $country['id'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($country['country_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-filter">Filter</button>
                    <a href="parcels.php" class="btn-clear">Clear</a>
                </form>
            </div>

            <!-- Parcels Table -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Customer</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Shipped</th>
                            <th>Est. Delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($shipments->num_rows > 0): ?>
                            <?php while ($shipment = $shipments->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($shipment['tracking_number']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($shipment['name']); ?><br>
                                        <small style="color: #999;"><?php echo htmlspecialchars($shipment['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($shipment['country_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $shipment['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $shipment['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($shipment['grand_total']); ?> <?php echo htmlspecialchars($shipment['currency_symbol']); ?></td>
                                    <td><?php echo ($shipment['shipped_date'] ? date('Y-m-d', strtotime($shipment['shipped_date'])) : 'Pending'); ?></td>
                                    <td><?php echo ($shipment['estimated_delivery'] ? date('Y-m-d', strtotime($shipment['estimated_delivery'])) : 'TBD'); ?></td>
                                    <td>
                                        <button onclick="openModal(<?php echo $shipment['id']; ?>, '<?php echo htmlspecialchars($shipment['status']); ?>', '<?php echo htmlspecialchars(str_replace("'", "\\'", $shipment['notes'] ?? '')); ?>')" class="action-btn btn-edit">Update</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">No parcels found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h2 style="color: #d4af37; margin-bottom: 20px;">Update Shipment Status</h2>

            <form method="POST" action="parcels.php">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="shipment_id" id="shipment_id">

                <div class="form-group">
                    <label for="status_select">Status:</label>
                    <select name="status" id="status_select" required>
                        <option value="pending">Pending</option>
                        <option value="shipped">Shipped</option>
                        <option value="in_transit">In Transit</option>
                        <option value="out_for_delivery">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="returned">Returned</option>
                        <option value="lost">Lost</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea name="notes" id="notes" placeholder="Add any notes about this shipment..."></textarea>
                </div>

                <button type="submit" class="btn-save">Update Status</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(shipmentId, currentStatus, notes) {
            document.getElementById('shipment_id').value = shipmentId;
            document.getElementById('status_select').value = currentStatus;
            document.getElementById('notes').value = notes;
            document.getElementById('statusModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
