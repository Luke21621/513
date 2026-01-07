<?php
// header.php - shared header (assumes session_start() already called in including file)
?>
<header>
    <div class="container navbar">
        <a href="index.php" class="brand"><img src="image/15.png" alt="GameHub Logo"></a>
        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="shop.php">Product</a>
            <a href="cart.php">Shopping Cart</a>
            <a href="subscribers.php">Customer List</a>
            <a href="recruitment.php">Careers</a>
            <a href="support.php">Support</a>
            <a href="discussion.php">Discussion</a>
            <a href="http://luke3.great-site.net/wordpress/register/">Register</a>
            <?php if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true): ?>
                <span style="margin-left: 1rem; color: #4a90e2; font-weight: 600;">Welcome, <?php echo htmlspecialchars($_SESSION['customer_name'] ?? $_SESSION['customer_email'] ?? 'Customer', ENT_QUOTES); ?></span>
                <a href="customer_logout.php" style="margin-left: 0.5rem; padding: 0.5rem 1rem; background: #6c757d; color: #ffffff; border-radius: 6px; text-decoration: none; font-size: 0.85rem;">Logout</a>
            <?php else: ?>
                <a href="customer_login.php">Customer Login</a>
            <?php endif; ?>
            <a href="admin.php">Admin Login</a>
        </nav>
    </div>
</header>


