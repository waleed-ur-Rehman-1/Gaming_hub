<?php
$pageTitle = 'Games';
require_once 'includes/db.php';

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$where = ["g.status = 'active'"];
$params = [];

if (!empty($category)) {
    $where[] = "c.category_slug = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $where[] = "(g.title LIKE ? OR g.description LIKE ? OR g.publisher LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$orderBy = "g.created_at DESC";
switch ($sort) {
    case 'popular': $orderBy = "g.download_count DESC"; break;
    case 'rating': $orderBy = "avg_rating DESC"; break;
    case 'name': $orderBy = "g.title ASC"; break;
}

$sql = "SELECT g.*, c.category_name, c.category_slug,
    (SELECT AVG(rating) FROM reviews WHERE game_id = g.game_id AND status = 'approved') as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE game_id = g.game_id AND status = 'approved') as review_count
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE " . implode(' AND ', $where) . "
    ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$games = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="search-section">
    <div class="search-box">
        <form action="games.php" method="GET">
            <input type="text" name="search" placeholder="Search games by name, genre, publisher..." value="<?= sanitizeInput($search) ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    
    <div class="filters">
        <button class="filter-btn <?= empty($category) ? 'active' : '' ?>" data-category="">All Games</button>
        <?php foreach ($categories as $cat): ?>
        <button class="filter-btn <?= $category == $cat['category_slug'] ? 'active' : '' ?>" data-category="<?= $cat['category_slug'] ?>">
            <?= $cat['category_name'] ?>
        </button>
        <?php endforeach; ?>
    </div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2><?= empty($search) ? 'All Games' : 'Search: ' . sanitizeInput($search) ?> 
            <span style="color: var(--text-muted); font-size: 1rem;">(<?= count($games) ?> results)</span>
        </h2>
        <select name="sort" onchange="location.href='games.php?category=<?= $category ?>&search=<?= urlencode($search) ?>&sort='+this.value" 
                style="padding: 10px 20px; background: var(--secondary-black); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); font-family: 'Rajdhani';">
            <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
            <option value="popular" <?= $sort == 'popular' ? 'selected' : '' ?>>Most Popular</option>
            <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>Highest Rated</option>
            <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>Name A-Z</option>
        </select>
    </div>
</section>

<section class="section" style="padding-top: 0;">
    <?php if (empty($games)): ?>
    <div style="text-align: center; padding: 80px 20px;">
        <i class="fas fa-search" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 20px;"></i>
        <h3>No games found</h3>
        <p style="color: var(--text-secondary);">Try adjusting your search or filters</p>
    </div>
    <?php else: ?>
    <div class="games-grid">
        <?php foreach ($games as $game): 
            $rating = round($game['avg_rating'] ?? 0, 1);
            $isWishlisted = false;
            if (isLoggedIn()) {
                $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND game_id = ?");
                $stmt->execute([getUserId(), $game['game_id']]);
                $isWishlisted = $stmt->fetch() ? true : false;
            }
        ?>
        <div class="game-card">
            <div class="game-card-image">
                <img src="uploads/games/<?= $game['cover_image'] ?>" alt="<?= sanitizeInput($game['title']) ?>" loading="lazy">
                <?php if ($game['featured']): ?>
                <span class="game-card-badge badge-featured">Featured</span>
                <?php elseif ($game['trending']): ?>
                <span class="game-card-badge badge-trending">Hot</span>
                <?php elseif ($game['new_release']): ?>
                <span class="game-card-badge badge-new">New</span>
                <?php endif; ?>
                <div class="game-card-overlay">
                    <a href="game-details.php?slug=<?= $game['slug'] ?>" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                </div>
            </div>
            <div class="game-card-content">
                <h3 class="game-card-title"><?= sanitizeInput($game['title']) ?></h3>
                <div class="game-card-meta">
                    <span><i class="fas fa-tag"></i> <?= $game['category_name'] ?></span>
                    <span><i class="fas fa-download"></i> <?= number_format($game['download_count']) ?></span>
                </div>
                <div class="game-card-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" style="<?= $i > round($rating) ? 'opacity: 0.3;' : '' ?>"></i>
                    <?php endfor; ?>
                    <span style="color: var(--text-muted); margin-left: 5px;"><?= $rating ?>/5</span>
                </div>
                <div class="game-card-footer">
                    <span class="game-price"><i class="fas fa-download"></i> Free</span>
                    <button class="wishlist-btn <?= $isWishlisted ? 'active' : '' ?>" data-game-id="<?= $game['game_id'] ?>">
                        <i class="<?= $isWishlisted ? 'fas' : 'far' ?> fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>