<?php
require_once 'db.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

// Get wishlist count if logged in
$wishlistCount = 0;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([getUserId()]);
    $wishlistCount = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <title><?= isset($pageTitle) ? $pageTitle . ' | ' : '' ?><?= SITE_NAME ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
</head>
<body>

<!-- Navigation -->
<<nav class="navbar">
    <a href="index.php" class="logo">
        <i class="fas fa-gamepad"></i>
        GAMING HUB
    </a>
    
    <ul class="nav-links">
        <li><a href="index.php" class="<?= $currentPage == 'index' ? 'active' : '' ?>">Home</a></li>
        <li><a href="games.php" class="<?= $currentPage == 'games' ? 'active' : '' ?>">Games</a></li>
        <li><a href="news.php" class="<?= $currentPage == 'news' ? 'active' : '' ?>">News</a></li>
        <li><a href="contact.php" class="<?= $currentPage == 'contact' ? 'active' : '' ?>">Contact</a></li>
        <?php if (isAdmin()): ?>
        <li><a href="admin/index.php" target="_blank">Admin</a></li>
        <?php endif; ?>
    </ul>
    
    <div class="nav-actions">
        <a href="search.php" class="btn btn-sm btn-secondary" style="padding: 8px 15px;">
            <i class="fas fa-search"></i>
        </a>
        
        <?php if (isLoggedIn()): ?>
        <a href="profile.php?tab=wishlist" class="btn btn-sm btn-secondary" style="padding: 8px 15px; position: relative;">
            <i class="fas fa-heart"></i>
            <?php if ($wishlistCount > 0): ?>
            <span style="position: absolute; top: -5px; right: -5px; background: var(--neon-pink); color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 0.75rem; display: flex; align-items: center; justify-content: center;"><?= $wishlistCount ?></span>
            <?php endif; ?>
        </a>
        
        <div class="user-menu">
            <img src="uploads/avatars/<?= $_SESSION['user_avatar'] ?? 'default-avatar.jpg' ?>" alt="User" class="user-avatar">
            <div class="dropdown">
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="profile.php?tab=downloads"><i class="fas fa-download"></i> Downloads</a>
                <a href="profile.php?tab=wishlist"><i class="fas fa-heart"></i> Wishlist</a>
                <a href="request-game.php"><i class="fas fa-plus-circle"></i> Request Game</a>
                <div style="border-top: 1px solid var(--border-color); margin: 5px 0;"></div>
                <a href="logout.php" style="color: var(--neon-pink);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <?php else: ?>
        <a href="login.php" class="btn btn-sm btn-secondary">Login</a>
        <a href="register.php" class="btn btn-sm btn-primary">Register</a>
        <?php endif; ?>
        
        <div class="mobile-toggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<div style="position: fixed; top: 80px; left: 50%; transform: translateX(-50%); z-index: 999; max-width: 500px; width: 90%;">
    <?= showFlash() ?>
</div>

<<main>