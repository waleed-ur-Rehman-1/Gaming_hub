<?php
require_once 'includes/db.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) redirect('games.php');

// Fetch game
$stmt = $pdo->prepare("SELECT g.*, c.category_name, c.category_slug,
    (SELECT AVG(rating) FROM reviews WHERE game_id = g.game_id AND status = 'approved') as avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE game_id = g.game_id AND status = 'approved') as review_count
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE g.slug = ? AND g.status = 'active'");
$stmt->execute([$slug]);
$game = $stmt->fetch();

if (!$game) redirect('games.php');

$pageTitle = $game['title'];

// Increment view count
$pdo->prepare("UPDATE games SET view_count = view_count + 1 WHERE game_id = ?")->execute([$game['game_id']]);

// Fetch screenshots
$screenshots = $pdo->prepare("SELECT * FROM screenshots WHERE game_id = ? ORDER BY sort_order");
$screenshots->execute([$game['game_id']]);
$screenshots = $screenshots->fetchAll();

// Fetch reviews
$reviews = $pdo->prepare("SELECT r.*, u.username, u.full_name, u.avatar 
    FROM reviews r 
    JOIN users u ON r.user_id = u.user_id 
    WHERE r.game_id = ? AND r.status = 'approved' 
    ORDER BY r.created_at DESC LIMIT 10");
$reviews->execute([$game['game_id']]);
$reviews = $reviews->fetchAll();

// Check wishlist
$isWishlisted = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND game_id = ?");
    $stmt->execute([getUserId(), $game['game_id']]);
    $isWishlisted = $stmt->fetch() ? true : false;
}

// Check if user already reviewed
$userReview = null;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND game_id = ?");
    $stmt->execute([getUserId(), $game['game_id']]);
    $userReview = $stmt->fetch();
}

// Related games
$related = $pdo->prepare("SELECT g.*, c.category_name,
    (SELECT AVG(rating) FROM reviews WHERE game_id = g.game_id AND status = 'approved') as avg_rating
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    WHERE g.category_id = ? AND g.game_id != ? AND g.status = 'active' 
    ORDER BY RAND() LIMIT 4");
$related->execute([$game['category_id'], $game['game_id']]);
$related = $related->fetchAll();

require_once 'includes/header.php';
?>

<section class="game-details">
    <!-- Game Hero -->
    <div class="game-hero" style="background-image: url('uploads/games/<?= $game['cover_image'] ?>');">
        <div class="game-hero-overlay"></div>
        <div class="game-hero-content">
            <div class="game-cover">
                <img src="uploads/games/<?= $game['cover_image'] ?>" alt="<?= sanitizeInput($game['title']) ?>">
            </div>
            <div class="game-info">
                <h1 class="game-title"><?= sanitizeInput($game['title']) ?></h1>
                <div class="game-meta-row">
                    <div class="game-meta-item">
                        <i class="fas fa-tag"></i>
                        <a href="games.php?category=<?= $game['category_slug'] ?>"><?= $game['category_name'] ?></a>
                    </div>
                    <div class="game-meta-item">
                        <i class="fas fa-building"></i> <?= sanitizeInput($game['publisher']) ?>
                    </div>
                    <div class="game-meta-item">
                        <i class="fas fa-calendar"></i> <?= formatDate($game['release_date']) ?>
                    </div>
                    <div class="game-meta-item">
                        <i class="fas fa-star"></i> <?= round($game['avg_rating'] ?? 0, 1) ?>/5 (<?= $game['review_count'] ?> reviews)
                    </div>
                    <div class="game-meta-item">
                        <i class="fas fa-download"></i> <?= number_format($game['download_count']) ?> downloads
                    </div>
                </div>
                <div class="game-actions">
                    <?php if (!empty($game['download_link'])): ?>
                    <a href="<?= $game['download_link'] ?>" class="btn btn-lg btn-primary download-btn" data-game-id="<?= $game['game_id'] ?>" target="_blank">
                        <i class="fas fa-download"></i> Download Now
                    </a>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn()): ?>
                    <button class="btn btn-lg btn-secondary wishlist-btn <?= $isWishlisted ? 'active' : '' ?>" data-game-id="<?= $game['game_id'] ?>" style="width: 60px;">
                        <i class="<?= $isWishlisted ? 'fas' : 'far' ?> fa-heart"></i>
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($game['trailer_url'])): ?>
                    <a href="<?= $game['trailer_url'] ?>" class="btn btn-lg btn-secondary" target="_blank">
                        <i class="fas fa-play"></i> Watch Trailer
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <div style="max-width: 1200px; margin: 0 auto;">
            <!-- Description -->
            <div style="margin-bottom: 50px;">
                <h2 style="margin-bottom: 20px;"><i class="fas fa-info-circle" style="color: var(--neon-blue);"></i> About This Game</h2>
                <div style="color: var(--text-secondary); line-height: 1.8; font-size: 1.1rem;">
                    <?= nl2br(sanitizeInput($game['description'])) ?>
                </div>
            </div>
            
            <!-- Screenshots -->
            <?php if (!empty($screenshots)): ?>
            <div style="margin-bottom: 50px;">
                <h2 style="margin-bottom: 20px;"><i class="fas fa-images" style="color: var(--neon-purple);"></i> Screenshots</h2>
                <div class="screenshots-grid">
                    <?php foreach ($screenshots as $shot): ?>
                    <div class="screenshot-item">
                        <img src="uploads/screenshots/<?= $shot['image_path'] ?>" alt="<?= sanitizeInput($shot['caption'] ?? 'Screenshot') ?>" loading="lazy">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- System Requirements -->
            <?php if (!empty($game['system_requirements'])): ?>
            <div style="margin-bottom: 50px; background: var(--secondary-black); border-radius: 16px; padding: 30px; border: 1px solid var(--border-color);">
                <h2 style="margin-bottom: 20px;"><i class="fas fa-desktop" style="color: var(--accent-green);"></i> System Requirements</h2>
                <div style="color: var(--text-secondary); line-height: 1.8;">
                    <?= nl2br(sanitizeInput($game['system_requirements'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Download Info -->
            <div style="margin-bottom: 50px; background: var(--secondary-black); border-radius: 16px; padding: 30px; border: 1px solid var(--border-color);">
                <h2 style="margin-bottom: 20px;"><i class="fas fa-download" style="color: var(--neon-blue);"></i> Download Information</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="text-align: center; padding: 20px; background: var(--tertiary-black); border-radius: 12px;">
                        <i class="fas fa-file-archive" style="font-size: 2rem; color: var(--neon-blue); margin-bottom: 10px;"></i>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">File Size</div>
                        <div style="font-family: 'Orbitron', sans-serif; font-size: 1.2rem; margin-top: 5px;"><?= $game['file_size'] ?? 'N/A' ?></div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: var(--tertiary-black); border-radius: 12px;">
                        <i class="fas fa-code-branch" style="font-size: 2rem; color: var(--neon-purple); margin-bottom: 10px;"></i>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">Version</div>
                        <div style="font-family: 'Orbitron', sans-serif; font-size: 1.2rem; margin-top: 5px;"><?= $game['version'] ?></div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: var(--tertiary-black); border-radius: 12px;">
                        <i class="fas fa-laptop" style="font-size: 2rem; color: var(--accent-green); margin-bottom: 10px;"></i>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">Platform</div>
                        <div style="font-family: 'Orbitron', sans-serif; font-size: 1.2rem; margin-top: 5px;"><?= $game['platform'] ?? 'Windows' ?></div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: var(--tertiary-black); border-radius: 12px;">
                        <i class="fas fa-calendar-check" style="font-size: 2rem; color: var(--neon-pink); margin-bottom: 10px;"></i>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">Release Date</div>
                        <div style="font-family: 'Orbitron', sans-serif; font-size: 1.2rem; margin-top: 5px;"><?= formatDate($game['release_date']) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Reviews -->
            <div style="margin-bottom: 50px;">
                <h2 style="margin-bottom: 30px;"><i class="fas fa-comments" style="color: var(--neon-purple);"></i> Reviews & Ratings</h2>
                
                <?php if (isLoggedIn() && !$userReview): ?>
                <div style="background: var(--secondary-black); border-radius: 16px; padding: 30px; margin-bottom: 30px; border: 1px solid var(--border-color);">
                    <h3 style="margin-bottom: 20px;">Write a Review</h3>
                    <form id="review-form">
                        <input type="hidden" name="game_id" value="<?= $game['game_id'] ?>">
                        <input type="hidden" name="rating" id="rating-value" value="5">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Your Rating</label>
                            <div class="rating-input">
                                <i class="fas fa-star active" data-value="1"></i>
                                <i class="fas fa-star active" data-value="2"></i>
                                <i class="fas fa-star active" data-value="3"></i>
                                <i class="fas fa-star active" data-value="4"></i>
                                <i class="fas fa-star active" data-value="5"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Your Review</label>
                            <textarea name="review_text" class="form-control" placeholder="Share your experience with this game..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Review
                        </button>
                    </form>
                </div>
                <?php elseif (!isLoggedIn()): ?>
                <div class="alert alert-info" style="margin-bottom: 30px;">
                    <i class="fas fa-info-circle"></i> Please <a href="login.php">login</a> to write a review.
                </div>
                <?php endif; ?>
                
                <?php if (empty($reviews)): ?>
                <div class="alert alert-info">No reviews yet. Be the first to review!</div>
                <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-user">
                            <img src="uploads/avatars/<?= $review['avatar'] ?? 'default-avatar.jpg' ?>" alt="" class="review-avatar">
                            <div>
                                <div style="font-weight: 600;"><?= sanitizeInput($review['full_name']) ?></div>
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
            
            <!-- Related Games -->
            <?php if (!empty($related)): ?>
            <div>
                <h2 style="margin-bottom: 30px;"><i class="fas fa-thumbs-up" style="color: var(--neon-blue);"></i> You Might Also Like</h2>
                <div class="games-grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
                    <?php foreach ($related as $rel): 
                        $relRating = round($rel['avg_rating'] ?? 0, 1);
                    ?>
                    <div class="game-card">
                        <div class="game-card-image">
                            <img src="uploads/games/<?= $rel['cover_image'] ?>" alt="<?= sanitizeInput($rel['title']) ?>" loading="lazy">
                            <div class="game-card-overlay">
                                <a href="game-details.php?slug=<?= $rel['slug'] ?>" class="btn btn-primary btn-sm">View</a>
                            </div>
                        </div>
                        <div class="game-card-content">
                            <h3 class="game-card-title" style="font-size: 1rem;"><?= sanitizeInput($rel['title']) ?></h3>
                            <div class="game-card-meta" style="font-size: 0.85rem;">
                                <span><?= $rel['category_name'] ?></span>
                                <span class="game-card-rating">
                                    <i class="fas fa-star"></i> <?= $relRating ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>