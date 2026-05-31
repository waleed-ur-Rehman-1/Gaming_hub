<?php
$pageTitle = 'Home';
require_once 'includes/header.php';

// Fetch data
$featuredGames = $pdo->query("SELECT g.*, c.category_name, c.category_slug, 
    (SELECT AVG(rating) FROM reviews WHERE game_id = g.game_id AND status = 'approved') as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE game_id = g.game_id AND status = 'approved') as review_count
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE g.featured = 1 AND g.status = 'active' 
    ORDER BY g.created_at DESC LIMIT 6")->fetchAll();

$trendingGames = $pdo->query("SELECT g.*, c.category_name,
    (SELECT AVG(rating) FROM reviews WHERE game_id = g.game_id AND status = 'approved') as avg_rating
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE g.trending = 1 AND g.status = 'active' 
    ORDER BY g.download_count DESC LIMIT 6")->fetchAll();

$newGames = $pdo->query("SELECT g.*, c.category_name,
    (SELECT AVG(rating) FROM reviews WHERE game_id = g.game_id AND status = 'approved') as avg_rating
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE g.new_release = 1 AND g.status = 'active' 
    ORDER BY g.release_date DESC LIMIT 6")->fetchAll();

$latestNews = $pdo->query("SELECT n.*, u.username, u.full_name 
    FROM news n 
    JOIN users u ON n.author_id = u.user_id 
    WHERE n.status = 'published' 
    ORDER BY n.created_at DESC LIMIT 3")->fetchAll();

$stats = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM games WHERE status = 'active') as total_games,
    (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
    (SELECT COUNT(*) FROM downloads) as total_downloads,
    (SELECT COUNT(*) FROM reviews WHERE status = 'approved') as total_reviews
")->fetch();
?>

<!-- Hero Section -->
<section class="hero">
    <video class="hero-video" autoplay muted loop playsinline poster="assets/images/hero-poster.jpg">
        <source src="assets/videos/hero-bg.mp4" type="video/mp4">
        <!-- Fallback to gradient if video not available -->
    </video>
    <div class="hero-overlay"></div>
    
    <div class="hero-content">
        <div class="hero-badge">
            <i class="fas fa-fire"></i> #1 Gaming Platform
        </div>
        <h1>
            Watch. <span>Play.</span> Download.<br>
            <span>Discover</span> the latest games today.
        </h1>
        <p>Explore thousands of games across all genres. Read reviews, watch trailers, and download your favorites instantly.</p>
        <div class="hero-buttons">
            <a href="games.php" class="btn btn-lg btn-primary">
                <i class="fas fa-rocket"></i> Explore Games
            </a>
            <a href="register.php" class="btn btn-lg btn-secondary">
                <i class="fas fa-user-plus"></i> Join Free
            </a>
        </div>
    </div>
    
    <div class="scroll-indicator">
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<!-- Stats Section -->
<section class="section" style="padding: 60px 5%; background: var(--secondary-black);">
    <div class="stats-grid" style="max-width: 1200px; margin: 0 auto;">
        <div class="stat-card" style="justify-content: center;">
            <div class="stat-icon"><i class="fas fa-gamepad"></i></div>
            <div class="stat-info">
                <h3><?= number_format($stats['total_games']) ?>+</h3>
                <p>Games Available</p>
            </div>
        </div>
        <div class="stat-card" style="justify-content: center;">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3><?= number_format($stats['total_users']) ?>+</h3>
                <p>Active Gamers</p>
            </div>
        </div>
        <div class="stat-card" style="justify-content: center;">
            <div class="stat-icon"><i class="fas fa-download"></i></div>
            <div class="stat-info">
                <h3><?= number_format($stats['total_downloads']) ?>+</h3>
                <p>Downloads</p>
            </div>
        </div>
        <div class="stat-card" style="justify-content: center;">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-info">
                <h3><?= number_format($stats['total_reviews']) ?>+</h3>
                <p>Reviews</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Games -->
<section class="section">
    <div class="section-header">
        <h2><i class="fas fa-crown" style="color: var(--neon-purple);"></i> Featured Games</h2>
        <p>Hand-picked premium games selected by our team</p>
        <div class="section-line"></div>
    </div>
    
    <div class="games-grid">
        <?php foreach ($featuredGames as $game): 
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
                <span class="game-card-badge badge-featured">Featured</span>
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
                    <span><i class="fas fa-calendar"></i> <?= formatDate($game['release_date']) ?></span>
                </div>
                <div class="game-card-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star<?= $i > round($rating) ? '-half-alt' : '' ?>" style="<?= $i > round($rating) ? 'opacity: 0.3;' : '' ?>"></i>
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
    
    <div style="text-align: center; margin-top: 40px;">
        <a href="games.php?category=featured" class="btn btn-secondary">View All Featured</a>
    </div>
</section>

<!-- Categories -->
<section class="section" style="background: var(--secondary-black);">
    <div class="section-header">
        <h2><i class="fas fa-th-large" style="color: var(--neon-blue);"></i> Browse Categories</h2>
        <p>Find games by your favorite genre</p>
        <div class="section-line"></div>
    </div>
    
    <div class="categories-grid">
        <?php foreach ($categories as $cat): ?>
        <a href="games.php?category=<?= $cat['category_slug'] ?>" class="category-card">
            <i class="fas fa-<?= $cat['icon'] ?>"></i>
            <h3><?= $cat['category_name'] ?></h3>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Trending Games -->
<section class="section">
    <div class="section-header">
        <h2><i class="fas fa-fire" style="color: var(--neon-pink);"></i> Trending Now</h2>
        <p>Most popular games this week</p>
        <div class="section-line"></div>
    </div>
    
    <div class="games-grid">
        <?php foreach ($trendingGames as $game): 
            $rating = round($game['avg_rating'] ?? 0, 1);
        ?>
        <div class="game-card">
            <div class="game-card-image">
                <img src="uploads/games/<?= $game['cover_image'] ?>" alt="<?= sanitizeInput($game['title']) ?>" loading="lazy">
                <span class="game-card-badge badge-trending"><i class="fas fa-fire"></i> Hot</span>
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
                    <button class="wishlist-btn" data-game-id="<?= $game['game_id'] ?>">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- New Releases -->
<section class="section" style="background: var(--secondary-black);">
    <div class="section-header">
        <h2><i class="fas fa-bolt" style="color: var(--accent-green);"></i> New Releases</h2>
        <p>Fresh games just added to our library</p>
        <div class="section-line"></div>
    </div>
    
    <div class="games-grid">
        <?php foreach ($newGames as $game): 
            $rating = round($game['avg_rating'] ?? 0, 1);
        ?>
        <div class="game-card">
            <div class="game-card-image">
                <img src="uploads/games/<?= $game['cover_image'] ?>" alt="<?= sanitizeInput($game['title']) ?>" loading="lazy">
                <span class="game-card-badge badge-new">NEW</span>
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
                    <span><i class="fas fa-calendar"></i> <?= formatDate($game['release_date']) ?></span>
                </div>
                <div class="game-card-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" style="<?= $i > round($rating) ? 'opacity: 0.3;' : '' ?>"></i>
                    <?php endfor; ?>
                    <span style="color: var(--text-muted); margin-left: 5px;"><?= $rating ?>/5</span>
                </div>
                <div class="game-card-footer">
                    <span class="game-price"><i class="fas fa-download"></i> Free</span>
                    <button class="wishlist-btn" data-game-id="<?= $game['game_id'] ?>">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Latest News -->
<section class="section">
    <div class="section-header">
        <h2><i class="fas fa-newspaper" style="color: var(--neon-purple);"></i> Gaming News</h2>
        <p>Stay updated with the latest in gaming</p>
        <div class="section-line"></div>
    </div>
    
    <div class="news-grid">
        <?php foreach ($latestNews as $news): ?>
        <article class="news-card">
            <div class="news-image">
                <img src="uploads/news/<?= $news['featured_image'] ?? 'default-news.jpg' ?>" alt="<?= sanitizeInput($news['title']) ?>" loading="lazy">
            </div>
            <div class="news-content">
                <div class="news-date">
                    <i class="fas fa-calendar-alt"></i> <?= formatDate($news['created_at']) ?>
                </div>
                <h3 class="news-title"><?= sanitizeInput($news['title']) ?></h3>
                <p class="news-excerpt"><?= truncateText($news['excerpt'] ?? $news['content'], 120) ?></p>
                <a href="news-details.php?slug=<?= $news['slug'] ?>" class="btn btn-sm btn-secondary">
                    Read More <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    
    <div style="text-align: center; margin-top: 40px;">
        <a href="news.php" class="btn btn-secondary">View All News</a>
    </div>
</section>

<!-- CTA Section -->
<section class="section" style="background: var(--gradient-glow); text-align: center;">
    <h2 style="font-size: 2.5rem; margin-bottom: 20px;">Ready to Start Gaming?</h2>
    <p style="color: var(--text-secondary); font-size: 1.2rem; max-width: 600px; margin: 0 auto 30px;">
        Join our community of gamers today. Create your account, build your wishlist, and start downloading.
    </p>
    <?php if (!isLoggedIn()): ?>
    <a href="register.php" class="btn btn-lg btn-primary">
        <i class="fas fa-rocket"></i> Create Free Account
    </a>
    <?php else: ?>
    <a href="games.php" class="btn btn-lg btn-primary">
        <i class="fas fa-gamepad"></i> Browse Games
    </a>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>