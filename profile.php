<?php
$pageTitle = 'Profile';
require_once 'includes/db.php';

if (!isLoggedIn()) redirect('login.php');

$userId = getUserId();
$activeTab = $_GET['tab'] ?? 'overview';

// Get user stats
$stats = $pdo->prepare("SELECT 
    (SELECT COUNT(*) FROM downloads WHERE user_id = ?) as total_downloads,
    (SELECT COUNT(*) FROM wishlist WHERE user_id = ?) as total_wishlist,
    (SELECT COUNT(*) FROM reviews WHERE user_id = ?) as total_reviews");
$stats->execute([$userId, $userId, $userId]);
$stats = $stats->fetch();

// Get downloads
$downloads = $pdo->prepare("SELECT d.*, g.title, g.slug, g.cover_image, g.category_id, c.category_name 
    FROM downloads d 
    JOIN games g ON d.game_id = g.game_id 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE d.user_id = ? ORDER BY d.download_date DESC");
$downloads->execute([$userId]);
$downloads = $downloads->fetchAll();

// Get wishlist
$wishlist = $pdo->prepare("SELECT w.*, g.title, g.slug, g.cover_image, g.release_date, c.category_name,
    (SELECT AVG(rating) FROM reviews WHERE game_id = g.game_id AND status = 'approved') as avg_rating
    FROM wishlist w 
    JOIN games g ON w.game_id = g.game_id 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE w.user_id = ? ORDER BY w.created_at DESC");
$wishlist->execute([$userId]);
$wishlist = $wishlist->fetchAll();

// Get reviews
$reviews = $pdo->prepare("SELECT r.*, g.title, g.slug, g.cover_image 
    FROM reviews r 
    JOIN games g ON r.game_id = g.game_id 
    WHERE r.user_id = ? ORDER BY r.created_at DESC");
$reviews->execute([$userId]);
$reviews = $reviews->fetchAll();

require_once 'includes/header.php';
?>

<section class="section" style="padding-top: 100px;">
    <div style="max-width: 1000px; margin: 0 auto;">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="uploads/avatars/<?= $_SESSION['user_avatar'] ?? 'default-avatar.jpg' ?>" alt="">
            </div>
            <div class="profile-info">
                <h2><?= sanitizeInput($_SESSION['user_name']) ?></h2>
                <p style="color: var(--text-secondary);">@<?= sanitizeInput($_SESSION['user_username']) ?></p>
                <div class="profile-stats">
                    <div class="profile-stat">
                        <h4><?= $stats['total_downloads'] ?></h4>
                        <p>Downloads</p>
                    </div>
                    <div class="profile-stat">
                        <h4><?= $stats['total_wishlist'] ?></h4>
                        <p>Wishlist</p>
                    </div>
                    <div class="profile-stat">
                        <h4><?= $stats['total_reviews'] ?></h4>
                        <p>Reviews</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn <?= $activeTab == 'overview' ? 'active' : '' ?>" data-tab="overview">Overview</button>
            <button class="tab-btn <?= $activeTab == 'downloads' ? 'active' : '' ?>" data-tab="downloads">Downloads</button>
            <button class="tab-btn <?= $activeTab == 'wishlist' ? 'active' : '' ?>" data-tab="wishlist">Wishlist</button>
            <button class="tab-btn <?= $activeTab == 'reviews' ? 'active' : '' ?>" data-tab="reviews">Reviews</button>
            <button class="tab-btn <?= $activeTab == 'settings' ? 'active' : '' ?>" data-tab="settings">Settings</button>
        </div>
        
        <!-- Overview -->
        <div id="overview" class="tab-content <?= $activeTab == 'overview' ? 'active' : '' ?>">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div style="background: var(--secondary-black); border-radius: 16px; padding: 25px; border: 1px solid var(--border-color);">
                    <h3 style="margin-bottom: 15px;"><i class="fas fa-user" style="color: var(--neon-blue);"></i> Account Info</h3>
                    <div style="color: var(--text-secondary); line-height: 2;">
                        <div><strong>Full Name:</strong> <?= sanitizeInput($_SESSION['user_name']) ?></div>
                        <div><strong>Username:</strong> <?= sanitizeInput($_SESSION['user_username']) ?></div>
                        <div><strong>Email:</strong> <?= sanitizeInput($_SESSION['user_email']) ?></div>
                        <div><strong>Role:</strong> <?= ucfirst($_SESSION['user_role']) ?></div>
                    </div>
                </div>
                
                <div style="background: var(--secondary-black); border-radius: 16px; padding: 25px; border: 1px solid var(--border-color);">
                    <h3 style="margin-bottom: 15px;"><i class="fas fa-chart-line" style="color: var(--neon-purple);"></i> Recent Activity</h3>
                    <?php if (empty($downloads) && empty($reviews)): ?>
                    <p style="color: var(--text-muted);">No recent activity</p>
                    <?php else: ?>
                    <div style="color: var(--text-secondary);">
                        <?php foreach (array_slice($downloads, 0, 3) as $dl): ?>
                        <div style="padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                            <i class="fas fa-download" style="color: var(--neon-blue);"></i> Downloaded 
                            <a href="game-details.php?slug=<?= $dl['slug'] ?>"><?= sanitizeInput($dl['title']) ?></a>
                            <div style="font-size: 0.85rem; color: var(--text-muted);"><?= formatDate($dl['download_date']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Downloads -->
        <div id="downloads" class="tab-content <?= $activeTab == 'downloads' ? 'active' : '' ?>">
            <?php if (empty($downloads)): ?>
            <div class="alert alert-info">No downloads yet. Start exploring games!</div>
            <?php else: ?>
            <div class="games-grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
                <?php foreach ($downloads as $dl): ?>
                <div class="game-card">
                    <div class="game-card-image">
                        <img src="uploads/games/<?= $dl['cover_image'] ?>" alt="">
                        <div class="game-card-overlay">
                            <a href="game-details.php?slug=<?= $dl['slug'] ?>" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                    <div class="game-card-content">
                        <h3 class="game-card-title" style="font-size: 1rem;"><?= sanitizeInput($dl['title']) ?></h3>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">
                            <i class="fas fa-calendar"></i> Downloaded <?= formatDate($dl['download_date']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Wishlist -->
        <div id="wishlist" class="tab-content <?= $activeTab == 'wishlist' ? 'active' : '' ?>">
            <?php if (empty($wishlist)): ?>
            <div class="alert alert-info">Your wishlist is empty. Browse games and add your favorites!</div>
            <?php else: ?>
            <div class="games-grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
                <?php foreach ($wishlist as $item): 
                    $rating = round($item['avg_rating'] ?? 0, 1);
                ?>
                <div class="game-card">
                    <div class="game-card-image">
                        <img src="uploads/games/<?= $item['cover_image'] ?>" alt="">
                        <div class="game-card-overlay">
                            <a href="game-details.php?slug=<?= $item['slug'] ?>" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                    <div class="game-card-content">
                        <h3 class="game-card-title" style="font-size: 1rem;"><?= sanitizeInput($item['title']) ?></h3>
                        <div class="game-card-meta" style="font-size: 0.85rem;">
                            <span><?= $item['category_name'] ?></span>
                            <span class="game-card-rating"><i class="fas fa-star"></i> <?= $rating ?></span>
                        </div>
                        <button class="wishlist-btn active" data-game-id="<?= $item['game_id'] ?>" style="margin-top: 10px;">
                            <i class="fas fa-heart"></i> Remove
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Reviews -->
        <div id="reviews" class="tab-content <?= $activeTab == 'reviews' ? 'active' : '' ?>">
            <?php if (empty($reviews)): ?>
            <div class="alert alert-info">No reviews yet. Share your thoughts on games you've played!</div>
            <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="review-user">
                        <img src="uploads/games/<?= $review['cover_image'] ?>" alt="" style="width: 60px; height: 60px; border-radius: 10px; object-fit: cover; margin-right: 15px;">
                        <div>
                            <div style="font-weight: 600; font-size: 1.1rem;">
                                <a href="game-details.php?slug=<?= $review['slug'] ?>"><?= sanitizeInput($review['title']) ?></a>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);"><?= formatDate($review['created_at']) ?></div>
                        </div>
                    </div>
                    <div class="review-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star" style="<?= $i > $review['rating'] ? 'opacity: 0.3;' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="review-text">
                    <?= nl2br(sanitizeInput($review['review_text'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Settings -->
        <div id="settings" class="tab-content <?= $activeTab == 'settings' ? 'active' : '' ?>">
            <div style="max-width: 600px;">
                <div style="background: var(--secondary-black); border-radius: 16px; padding: 30px; border: 1px solid var(--border-color); margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px;">Change Password</h3>
                    <form action="api/update-password.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>