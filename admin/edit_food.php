<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get food id
$food_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($food_id <= 0) {
    header('Location: food_list.php');
    exit;
}

$errors      = [];
$success_msg = [];

// Fetch categories for dropdown
$categories = [];
$cat_res = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
if ($cat_res) {
    while ($row = $cat_res->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch current food data
$stmt = $conn->prepare("SELECT * FROM foods WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $food_id);
$stmt->execute();
$res  = $stmt->get_result();
$food = $res->fetch_assoc();
$stmt->close();

if (!$food) {
    header('Location: food_list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_food'])) {
    $name        = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price       = (float)($_POST['price'] ?? 0);
    $status      = $_POST['status'] ?? 'active';
    $short_desc  = trim($_POST['short_description'] ?? '');
    $full_desc   = trim($_POST['full_description'] ?? '');
    $old_image   = $_POST['old_image'] ?? null;

    if ($name === '') {
        $errors[] = 'Food name is required.';
    }
    if ($category_id <= 0) {
        $errors[] = 'Please select a category.';
    }
    if ($price <= 0) {
        $errors[] = 'Price must be greater than 0.';
    }

    // Optional new image upload
    $image_name = $old_image; // default: keep old image
    if (!empty($_FILES['image']['name'])) {
        $file_name = $_FILES['image']['name'];
        $tmp_name  = $_FILES['image']['tmp_name'];
        $file_err  = $_FILES['image']['error'];

        if ($file_err === UPLOAD_ERR_OK) {
            $ext     = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $allowed)) {
                $errors[] = 'Only JPG, PNG, or GIF images are allowed.';
            } else {
                $new_name  = 'food_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $dest_path = __DIR__ . '/../uploads/food_images/' . $new_name;
                if (move_uploaded_file($tmp_name, $dest_path)) {
                    $image_name = $new_name;
                    // Delete old image file if exists
                    if (!empty($old_image)) {
                        $old_path = __DIR__ . '/../uploads/food_images/' . $old_image;
                        if (file_exists($old_path)) {
                            @unlink($old_path);
                        }
                    }
                } else {
                    $errors[] = 'Failed to upload new image. Check folder permissions.';
                }
            }
        } else {
            $errors[] = 'Error uploading image file.';
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE foods
            SET name = ?, category_id = ?, price = ?, image = ?, 
                short_description = ?, full_description = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param(
            'sidssssi',
            $name,
            $category_id,
            $price,
            $image_name,
            $short_desc,
            $full_desc,
            $status,
            $food_id
        );
        if ($stmt->execute()) {
            $success_msg = 'Food item updated successfully.';
            // Refresh data in $food
            $food['name']              = $name;
            $food['category_id']       = $category_id;
            $food['price']             = $price;
            $food['image']             = $image_name;
            $food['short_description'] = $short_desc;
            $food['full_description']  = $full_desc;
            $food['status']            = $status;
        } else {
            $errors[] = 'Failed to update food item.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Food Item - Admin - FoodHub</title>
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
            <h1>Edit Food Item</h1>
            <p>Update menu item details.</p>
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

            <form action="edit_food.php?id=<?php echo (int)$food_id; ?>" method="post"
                  enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($food['image'] ?? ''); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Food Name *</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php echo htmlspecialchars($food['name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>"
                                    <?php echo ((int)$food['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
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
                            value="<?php echo htmlspecialchars($food['price']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status">
                            <option value="active" <?php echo ($food['status'] === 'active') ? 'selected' : ''; ?>>
                                Active
                            </option>
                            <option value="inactive" <?php echo ($food['status'] === 'inactive') ? 'selected' : ''; ?>>
                                Inactive
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Current Image</label>
                    <?php if (!empty($food['image'])): ?>
                        <div class="current-image">
                            <img src="../uploads/food_images/<?php echo htmlspecialchars($food['image']); ?>"
                                 alt="<?php echo htmlspecialchars($food['name']); ?>">
                        </div>
                    <?php else: ?>
                        <p>No image uploaded.</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="image">Change Image (optional)</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small>Leave empty to keep existing image.</small>
                </div>

                <div class="form-group">
                    <label for="short_description">Short Description</label>
                    <textarea id="short_description" name="short_description" rows="2"><?php
                        echo htmlspecialchars($food['short_description'] ?? '');
                    ?></textarea>
                </div>

                <div class="form-group">
                    <label for="full_description">Full Description</label>
                    <textarea id="full_description" name="full_description" rows="4"><?php
                        echo htmlspecialchars($food['full_description'] ?? '');
                    ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_food" class="btn btn-primary">Update Food</button>
                    <a href="food_list.php" class="btn btn-outline">Back to Food List</a>
                </div>
            </form>
        </section>
    </main>
</div>

<script src="../assets/js/admin.js"></script>
</body>
</html>
