<?php require_once '../includes/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin' ?> | <?= SITE_NAME ?> Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a href="../index.php" class="logo" style="font-size: 1.4rem;">
            <i class="fas fa-gamepad"></i> ADMIN
        </a>
        <ul class="admin-nav">
            <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="games.php" class="<?= basename($_SERVER['PHP_SELF']) == 'games.php' ? 'active' : '' ?>"><i class="fas fa-gamepad"></i> Games</a></li>
            <li><a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="news.php" class="<?= basename($_SERVER['PHP_SELF']) == 'news.php' ? 'active' : '' ?>"><i class="fas fa-newspaper"></i> News</a></li>
            <li><a href="reviews.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : '' ?>"><i class="fas fa-star"></i> Reviews</a></li>
            <li><a href="messages.php" class="<?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Messages</a></li>
            <li><a href="requests.php" class="<?= basename($_SERVER['PHP_SELF']) == 'requests.php' ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i> Requests</a></li>
            <li style="margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 15px;"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    
    <main class="admin-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2><?= $pageTitle ?? 'Dashboard' ?></h2>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="color: var(--text-secondary);">Welcome, <?= $_SESSION['user_name'] ?></span>
                <button class="admin-toggle" style="display: none; background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>