<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Simple admin auth check (you will set this after login)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Basic dashboard stats (you must have these tables in DB) [web:1]
$stats = [
    'total_orders'        => 0,
    'pending_orders'      => 0,
    'completed_orders'    => 0,
    'canceled_orders'     => 0,
    'total_food_items'    => 0,
    'total_categories'    => 0,
    'today_orders'        => 0,
    'today_revenue'       => 0.0,
];

// Total orders
$res = $conn->query("SELECT COUNT(*) AS cnt FROM orders");
if ($res && $row = $res->fetch_assoc()) {
    $stats['total_orders'] = (int)$row['cnt'];
}

// Pending / completed / canceled
$res = $conn->query("
    SELECT status, COUNT(*) AS cnt 
    FROM orders 
    GROUP BY status
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $status = strtolower($row['status']);
        if ($status === 'pending') {
            $stats['pending_orders'] = (int)$row['cnt'];
        } elseif ($status === 'completed' || $status === 'delivered') {
            $stats['completed_orders'] = (int)$row['cnt'];
        } elseif ($status === 'canceled' || $status === 'cancelled') {
            $stats['canceled_orders'] = (int)$row['cnt'];
        }
    }
}

// Total food items
$res = $conn->query("SELECT COUNT(*) AS cnt FROM foods");
if ($res && $row = $res->fetch_assoc()) {
    $stats['total_food_items'] = (int)$row['cnt'];
}

// Total categories
$res = $conn->query("SELECT COUNT(*) AS cnt FROM categories");
if ($res && $row = $res->fetch_assoc()) {
    $stats['total_categories'] = (int)$row['cnt'];
}

// Today's orders and revenue (based on created_at date) [web:1]
$res = $conn->query("
    SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS revenue
    FROM orders
    WHERE DATE(created_at) = CURDATE()
");
if ($res && $row = $res->fetch_assoc()) {
    $stats['today_orders']  = (int)$row['cnt'];
    $stats['today_revenue'] = (float)$row['revenue'];
}

// Recent orders for quick view
$recent_orders = [];
$res = $conn->query("
    SELECT id, customer_name, phone, total_amount, status, created_at 
    FROM orders 
    ORDER BY created_at DESC 
    LIMIT 10
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - FoodHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <span>FoodHub Admin</span>
        </div>
        <nav class="admin-nav">
            <a href="index.php" class="active">Dashboard</a>
            <a href="food_list.php">Foods</a>
            <a href="categories.php">Categories</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php" class="logout-link">Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <h1>Dashboard</h1>
            <p>Overview of orders, menu items and sales.</p>
        </header>

        <!-- Top Stats -->
        <section class="admin-stats-grid">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <p class="stat-number"><?php echo $stats['total_orders']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <p class="stat-number"><?php echo $stats['pending_orders']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Completed Orders</h3>
                <p class="stat-number"><?php echo $stats['completed_orders']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Canceled Orders</h3>
                <p class="stat-number"><?php echo $stats['canceled_orders']; ?></p>
            </div>
        </section>

        <!-- Middle Stats -->
        <section class="admin-stats-grid">
            <div class="stat-card">
                <h3>Total Food Items</h3>
                <p class="stat-number"><?php echo $stats['total_food_items']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Categories</h3>
                <p class="stat-number"><?php echo $stats['total_categories']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Today's Orders</h3>
                <p class="stat-number"><?php echo $stats['today_orders']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Today's Revenue</h3>
                <p class="stat-number">৳ <?php echo number_format($stats['today_revenue'], 2); ?></p>
            </div>
        </section>

        <!-- Recent Orders Table -->
        <section class="admin-section">
            <h2>Recent Orders</h2>
            <?php if (!empty($recent_orders)): ?>
                <div class="table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Total (৳)</th>
                                <th>Status</th>
                                <th>Placed At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recent_orders as $o): ?>
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
                                <td><?php echo htmlspecialchars($o['created_at']); ?></td>
                                <td>
                                    <a href="orders.php?view=<?php echo (int)$o['id']; ?>" class="btn-small">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No orders found.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

<script src="../assets/js/admin.js"></script>
</body>
</html>
