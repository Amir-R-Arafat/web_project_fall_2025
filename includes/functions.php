<?php
// includes/functions.php
// Reusable helper functions for FoodHub

/**
 * Get featured categories for homepage.
 * Expects categories table: id, name, image, status, created_at, etc. [web:30]
 */
function getFeaturedCategories(mysqli $conn, int $limit = 4): array
{
    $data = [];
    $sql = "SELECT id, name, image 
            FROM categories 
            WHERE status = 'active' 
            ORDER BY id DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $limit);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }
    $stmt->close();
    return $data;
}

/**
 * Get featured foods for homepage.
 * You can add a 'featured' column in foods table and filter it if you want. [web:30]
 */
function getFeaturedFoods(mysqli $conn, int $limit = 8): array
{
    $data = [];
    $sql = "SELECT id, name, price, image, short_description 
            FROM foods 
            WHERE status = 'active'
            ORDER BY id DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $limit);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }
    $stmt->close();
    return $data;
}

/**
 * Get all active categories (for menu sidebar). [web:336]
 */
function getAllActiveCategories(mysqli $conn): array
{
    $data = [];
    $sql = "SELECT id, name 
            FROM categories 
            WHERE status = 'active' 
            ORDER BY name ASC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Get foods filtered by category id (for menu page). [web:30]
 */
function getFoodsByCategory(mysqli $conn, int $category_id): array
{
    $data = [];
    $sql = "SELECT id, name, price, image, short_description 
            FROM foods 
            WHERE status = 'active' AND category_id = ?
            ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $category_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }
    $stmt->close();
    return $data;
}

/**
 * Get all active foods (no filter) for menu page. [web:214]
 */
function getAllActiveFoods(mysqli $conn): array
{
    $data = [];
    $sql = "SELECT id, name, price, image, short_description 
            FROM foods 
            WHERE status = 'active'
            ORDER BY id DESC";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Get total items count in the cart (sum of quantities). [web:23]
 */
function getCartItemCount(): int
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int)($item['quantity'] ?? 0);
    }
    return $count;
}

/**
 * Get cart subtotal (without delivery). [web:39]
 */
function getCartSubtotal(): float
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $price = (float)($item['price'] ?? 0);
        $qty   = (int)($item['quantity'] ?? 0);
        $total += $price * $qty;
    }
    return $total;
}

/**
 * Escape output helper to avoid repeating htmlspecialchars. [web:299]
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
