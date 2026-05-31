<?php
require_once '../includes/db.php';

if (!isAdmin()) redirect('../index.php');

$pageTitle = 'Admin Dashboard';

// Stats
$stats = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
    (SELECT COUNT(*) FROM games) as total_games,
    (SELECT COUNT(*) FROM downloads) as total_downloads,
    (SELECT COUNT(*) FROM reviews) as total_reviews,
    (SELECT COUNT(*) FROM contact_messages WHERE status = 'unread') as unread_messages,
    (SELECT COUNT(*) FROM game_requests WHERE status = 'pending') as pending_requests
")->fetch();

// Recent users
$recentUsers = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent games
$recentGames = $pdo->query("SELECT g.*, c.category_name FROM games g JOIN categories c ON g.category_id = c.category_id ORDER BY g.created_at DESC LIMIT 5")->fetchAll();

// Recent reviews
$recentReviews = $pdo->query("SELECT r.*, u.username, g.title FROM reviews r JOIN users u ON r.user_id = u.user_id JOIN games g ON r.game_id = g.game_id ORDER BY r.created_at DESC LIMIT 5")->fetchAll();

require_once 'admin-header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?= number_format($stats['total_users']) ?></h3>
            <p>Total Users</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-gamepad"></i></div>
        <div class="stat-info">
            <h3><?= number_format($stats['total_games']) ?></h3>
            <p>Total Games</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-download"></i></div>
        <div class="stat-info">
            <h3><?= number_format($stats['total_downloads']) ?></h3>
            <p>Total Downloads</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-star"></i></div>
        <div class="stat-info">
            <h3><?= number_format($stats['total_reviews']) ?></h3>
            <p>Total Reviews</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
    <!-- Recent Users -->
    <div style="background: var(--secondary-black); border-radius: 16px; padding: 25px; border: 1px solid var(--border-color);">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-users" style="color: var(--neon-blue);"></i> Recent Users</h3>
        <table class="data-table">
            <thead>
                <tr><th>User</th><th>Joined</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentUsers as $user): ?>
                <tr>
                    <td><?= sanitizeInput($user['full_name']) ?></td>
                    <td><?= formatDate($user['created_at']) ?></td>
                    <td><span style="color: var(--accent-green);"><?= ucfirst($user['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Recent Games -->
    <div style="background: var(--secondary-black); border-radius: 16px; padding: 25px; border: 1px solid var(--border-color);">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-gamepad" style="color: var(--neon-purple);"></i> Recent Games</h3>
        <table class="data-table">
            <thead>
                <tr><th>Game</th><th>Category</th><th>Downloads</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentGames as $game): ?>
                <tr>
                    <td><?= sanitizeInput($game['title']) ?></td>
                    <td><?= $game['category_name'] ?></td>
                    <td><?= number_format($game['download_count']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'admin-footer.php'; ?>