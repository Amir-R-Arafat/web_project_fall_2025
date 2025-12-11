<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Only logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle status update action (simple POST form) [web:1][web:183]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';

    $allowed_status = ['pending', 'processing', 'delivered', 'canceled'];
    if ($order_id > 0 && in_array($new_status, $allowed_status, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? LIMIT 1");
        $stmt->bind_param('si', $new_status, $order_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['admin_message'] = 'Order status updated.';
    }
    header('Location: orders.php');
    exit;
}

// Optional flash message
$flash_message = $_SESSION['admin_message'] ?? '';
unset($_SESSION['admin_message']);

// Optional filter by status via GET (?status=pending) [web:1]
$filter_status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
$where_clause = '';
if (in_array($filter_status, ['pending', 'processing', 'delivered', 'canceled'], true)) {
    $where_clause = "WHERE status = '" . $conn->real_escape_string($filter_status) . "'";
}

// Fetch orders list
$orders = [];
$sql = "
    SELECT id, customer_name, phone, total_amount, status, tracking_code, created_at 
    FROM orders
    $where_clause
    ORDER BY created_at DESC
";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders - Admin - FoodHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <span>FoodHub Admin</span>
        </div>
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="food_list.php">Foods</a>
            <a href="categories.php">Categories</a>
            <a href="orders.php" class="active">Orders</a>
            <a href="logout.php" class="logout-link">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Orders</h1>
            <p>View and manage all customer orders.</p>
        </header>

        <section class="admin-section">
            <div class="admin-section-header">
                <!-- Simple status filter -->
                <form action="orders.php" method="get" class="filter-form">
                    <label for="status">Filter by status:</label>
                    <select name="status" id="status" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $filter_status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="delivered" <?php echo $filter_status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="canceled" <?php echo $filter_status === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                    </select>
                    <noscript>
                        <button type="submit" class="btn-small">Apply</button>
                    </noscript>
                </form>
            </div>

            <?php if ($flash_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($flash_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($orders)): ?>
                <div class="table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Total (৳)</th>
                                <th>Status</th>
                                <th>Tracking</th>
                                <th>Placed At</th>
                                <th>Change Status</th>
                                <th>View</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>#<?php echo (int)$o['id']; ?></td>
                                <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($o['phone']); ?></td>
                                <td><?php echo number_format($o['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars(strtolower($o['status'])); ?>">
                                        <?php echo htmlspecialchars(ucfirst($o['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($o['tracking_code']); ?></td>
                                <td><?php echo htmlspecialchars($o['created_at']); ?></td>
                                <td>
                                    <form action="orders.php" method="post" class="inline-form">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                                        <select name="status">
                                            <option value="pending" <?php echo $o['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $o['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="delivered" <?php echo $o['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="canceled" <?php echo $o['status'] === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-small">
                                            Update
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <!-- You can create view_order.php to show full details if you want [web:88] -->
                                    <a href="../track.php?code=<?php echo urlencode($o['tracking_code']); ?>" class="btn-small">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No orders found for the selected filter.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

<script src="../assets/js/admin.js"></script>
</body>
</html>
