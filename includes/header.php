<?php
// includes/header.php

// Calculate cart item count (sum of quantities)
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += (int)($item['quantity'] ?? 0);
    }
}

// Simple current page name for active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="site-header">
    <div class="header-inner">
        <a href="/project/index.php" class="logo">
            <span class="logo-img-wrap">
                <img src="assets/images/logo.png.jpg" alt="FoodHub">
            </span>
            <span class="logo-text">FoodHub</span>
        </a>

        <!-- Mobile menu toggle (works with script.js) -->
        <button type="button" class="menu-toggle" aria-label="Toggle navigation">
            ☰
        </button>

        <nav class="main-nav">
            <a href="/project/index.php"
               class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                Home
            </a>
            <a href="/project/menu.php"
               class="<?php echo $current_page === 'menu.php' ? 'active' : ''; ?>">
                Menu
            </a>
            <a href="/project/contact.php"
               class="<?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">
                Contact
            </a>
            <a href="/project/cart.php"
               class="cart-link <?php echo $current_page === 'cart.php' ? 'active' : ''; ?>">
                Cart
                <?php if ($cart_count > 0): ?>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        </nav>
    </div>
</header>
