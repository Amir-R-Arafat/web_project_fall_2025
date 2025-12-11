<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Only logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Optional flash message from add/edit/delete
$flash_message = $_SESSION['admin_message'] ?? '';
unset($_SESSION['admin_message']);

// Fetch all foods with category name [web:1]
$foods = [];
$sql = "
    SELECT f.id, f.name, f.price, f.image, f.status, f.created_at, c.name AS category_name
    FROM foods f
    LEFT JOIN categories c ON c.id = f.category_id
    ORDER BY f.created_at DESC
";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $foods[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Food List - Admin - FoodHub</title>
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
            <a href="food_list.php" class="active">Foods</a>
            <a href="categories.php">Categories</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php" class="logout-link">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Food Items</h1>
            <p>Manage all menu items of FoodHub.</p>
        </header>

        <section class="admin-section">
            <div class="admin-section-header">
                <a href="add_food.php" class="btn btn-primary">+ Add New Food</a>
            </div>

            <?php if ($flash_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($flash_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($foods)): ?>
                <div class="table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price (৳)</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($foods as $f): ?>
                            <tr>
                                <td>#<?php echo (int)$f['id']; ?></td>
                                <td>
                                    <?php if (!empty($f['image'])): ?>
                                        <img
                                            src="../uploads/food_images/<?php echo htmlspecialchars($f['image']); ?>"
                                            alt="<?php echo htmlspecialchars($f['name']); ?>"
                                            class="table-thumb"
                                        >
                                    <?php else: ?>
                                        <span class="muted">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($f['name']); ?></td>
                                <td><?php echo htmlspecialchars($f['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo number_format($f['price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars(strtolower($f['status'])); ?>">
                                        <?php echo htmlspecialchars(ucfirst($f['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($f['created_at']); ?></td>
                                <td>
                                    <a href="edit_food.php?id=<?php echo (int)$f['id']; ?>" class="btn-small">
                                        Edit
                                    </a>
                                    <a
                                        href="delete_food.php?id=<?php echo (int)$f['id']; ?>"
                                        class="btn-small btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this food item?');"
                                    >
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No food items found. Start by adding a new one.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

<script src="../assets/js/admin.js"></script>
</body>
</html>
