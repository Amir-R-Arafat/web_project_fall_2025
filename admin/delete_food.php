<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Only logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get id
$food_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($food_id <= 0) {
    header('Location: food_list.php');
    exit;
}

// First get image name
$stmt = $conn->prepare("SELECT image FROM foods WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $food_id);
$stmt->execute();
$res = $stmt->get_result();
$food = $res->fetch_assoc();
$stmt->close();

// Delete row
$stmt = $conn->prepare("DELETE FROM foods WHERE id = ?");
$stmt->bind_param('i', $food_id);
$stmt->execute();
$stmt->close();

// Delete image file if exists
if (!empty($food['image'])) {
    $path = __DIR__ . '/../uploads/food_images/' . $food['image'];
    if (file_exists($path)) {
        @unlink($path);
    }
}

header('Location: food_list.php');
exit;
