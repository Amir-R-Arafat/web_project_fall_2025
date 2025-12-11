 <?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success_msg = '';

// Fetch categories for dropdown
$categories = [];
$cat_res = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC"); // [web:22]
if ($cat_res) {
    while ($row = $cat_res->fetch_assoc()) {
        $categories[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_food'])) {
    $name        = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price       = (float)($_POST['price'] ?? 0);
    $status      = $_POST['status'] ?? 'active';
    $short_desc  = trim($_POST['short_description'] ?? '');
    $full_desc   = trim($_POST['full_description'] ?? '');

    if ($name === '') {
        $errors[] = 'Food name is required.';
    }
    if ($category_id <= 0) {
        $errors[] = 'Please select a category.';
    }
    if ($price <= 0) {
        $errors[] = 'Price must be greater than 0.';
    }

    // Image upload handling [web:154][web:166]
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
        $file_name = $_FILES['image']['name'];
        $tmp_name  = $_FILES['image']['tmp_name'];
        $file_err  = $_FILES['image']['error'];

        if ($file_err === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $allowed)) {
                $errors[] = 'Only JPG, PNG, or GIF images are allowed.';
            } else {
                $new_name = 'food_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $dest_path = __DIR__ . '/../uploads/food_images/' . $new_name;
                if (move_uploaded_file($tmp_name, $dest_path)) { // [web:160]
                    $image_name = $new_name;
                } else {
                    $errors[] = 'Failed to upload image. Check folder permissions.';
                }
            }
        } else {
            $errors[] = 'Error uploading image file.';
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO foods (name, category_id, price, image, short_description, full_description, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            'sidssss',
            $name,
            $category_id,
            $price,
            $image_name,
            $short_desc,
            $full_desc,
            $status
        );
        if ($stmt->execute()) {
            $success_msg = 'Food item added successfully.';
            // Clear POST values
            $_POST = [];
        } else {
            $errors[] = 'Failed to add food item.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Food Item - Admin - FoodHub</title>
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
            <h1>Add New Food Item</h1>
            <p>Create a new menu item for FoodHub.</p>
        </header>

        <section class="admin-section">
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_msg); ?>
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

            <form action="add_food.php" method="post" enctype="multipart/form-data" class="admin-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Food Name *</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>"
                                    <?php
                                    $sel = (int)($_POST['category_id'] ?? 0);
                                    echo $sel === (int)$cat['id'] ? 'selected' : '';
                                    ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (৳) *</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            id="price"
                            name="price"
                            value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo (($_POST['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>
                                Active
                            </option>
                            <option value="inactive" <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>
                                Inactive
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Food Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small>Recommended: JPG/PNG, max ~2MB. Saved in uploads/food_images/</small>
                </div>

                <div class="form-group">
                    <label for="short_description">Short Description</label>
                    <textarea id="short_description" name="short_description" rows="2"><?php
                        echo htmlspecialchars($_POST['short_description'] ?? '');
                    ?></textarea>
                </div>

                <div class="form-group">
                    <label for="full_description">Full Description</label>
                    <textarea id="full_description" name="full_description" rows="4"><?php
                        echo htmlspecialchars($_POST['full_description'] ?? '');
                    ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" name="add_food" class="btn btn-primary">Save Food</button>
                    <a href="food_list.php" class="btn btn-outline">Back to Food List</a>
                </div>
            </form>
        </section>
    </main>
</div>

<script src="../assets/js/admin.js"></script>
</body>
</html>
