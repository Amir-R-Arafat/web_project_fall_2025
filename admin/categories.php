<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$errors  = [];
$success = '';

// Handle add / update category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $cat_id = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;

    if ($name === '') {
        $errors[] = 'Category name is required.';
    }

    if (empty($errors)) {
        if ($cat_id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE categories SET name = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('ssi', $name, $status, $cat_id);
            if ($stmt->execute()) {
                $success = 'Category updated successfully.';
            } else {
                $errors[] = 'Failed to update category.';
            }
            $stmt->close();
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO categories (name, status, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param('ss', $name, $status);
            if ($stmt->execute()) {
                $success = 'Category added successfully.';
            } else {
                $errors[] = 'Failed to add category.';
            }
            $stmt->close();
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id > 0) {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param('i', $del_id);
        $stmt->execute();
        $stmt->close();
        header('Location: categories.php');
        exit;
    }
}

// For edit form
$edit_cat = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    if ($edit_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $edit_cat = $res->fetch_assoc();
        $stmt->close();
    }
}

// Fetch all categories
$cat_list = [];
$res = $conn->query("SELECT * FROM categories ORDER BY created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $cat_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories - Admin - FoodHub</title>
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
            <a href="categories.php" class="active">Categories</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php" class="logout-link">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Categories</h1>
            <p>Manage food categories.</p>
        </header>

        <section class="admin-section">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="admin-grid">
                <!-- Form -->
                <div class="admin-card">
                    <h2><?php echo $edit_cat ? 'Edit Category' : 'Add New Category'; ?></h2>

                    <form action="categories.php<?php echo $edit_cat ? '?edit=' . (int)$edit_cat['id'] : ''; ?>"
                          method="post" class="admin-form">
                        <input type="hidden" name="cat_id"
                               value="<?php echo $edit_cat ? (int)$edit_cat['id'] : 0; ?>">

                        <div class="form-group">
                            <label for="name">Category Name *</label>
                            <input type="text" id="name" name="name"
                                   value="<?php echo htmlspecialchars($edit_cat['name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status">
                                <option value="active" <?php
                                echo (($edit_cat['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>
                                    Active
                                </option>
                                <option value="inactive" <?php
                                echo (($edit_cat['status'] ?? 'active') === 'inactive') ? 'selected' : ''; ?>>
                                    Inactive
                                </option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edit_cat ? 'Update Category' : 'Add Category'; ?>
                            </button>
                            <?php if ($edit_cat): ?>
                                <a href="categories.php" class="btn btn-outline">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- List -->
                <div class="admin-card">
                    <h2>All Categories</h2>

                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($cat_list)): ?>
                            <tr>
                                <td colspan="5">No categories found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cat_list as $cat): ?>
                                <tr>
                                    <td><?php echo (int)$cat['id']; ?></td>
                                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                    <td><?php echo htmlspecialchars($cat['status']); ?></td>
                                    <td><?php echo htmlspecialchars($cat['created_at']); ?></td>
                                    <td>
                                        <a href="categories.php?edit=<?php echo (int)$cat['id']; ?>" class="btn btn-small">
                                            Edit
                                        </a>
                                        <a href="categories.php?delete=<?php echo (int)$cat['id']; ?>"
                                           class="btn btn-small btn-danger"
                                           onclick="return confirm('Delete this category?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<script src="../assets/js/admin.js"></script>
</body>
</html>
