<?php
$pageTitle = 'Gaming News';
require_once 'includes/db.php';

$news = $pdo->query("SELECT n.*, u.username, u.full_name 
    FROM news n 
    JOIN users u ON n.author_id = u.user_id 
    WHERE n.status = 'published' 
    ORDER BY n.created_at DESC")->fetchAll();

require_once 'includes/header.php';
?>

<section class="section" style="padding-top: 100px;">
    <div class="section-header">
        <h2><i class="fas fa-newspaper" style="color: var(--neon-blue);"></i> Gaming News</h2>
        <p>Latest updates, announcements, and gaming blogs</p>
        <div class="section-line"></div>
    </div>
    
    <div class="news-grid">
        <?php foreach ($news as $item): ?>
        <article class="news-card">
            <div class="news-image">
                <img src="uploads/news/<?= $item['featured_image'] ?? 'default-news.jpg' ?>" alt="<?= sanitizeInput($item['title']) ?>" loading="lazy">
            </div>
            <div class="news-content">
                <div class="news-date">
                    <i class="fas fa-calendar-alt"></i> <?= formatDate($item['created_at']) ?> by <?= sanitizeInput($item['full_name']) ?>
                </div>
                <h3 class="news-title"><?= sanitizeInput($item['title']) ?></h3>
                <p class="news-excerpt"><?= truncateText($item['excerpt'] ?? $item['content'], 150) ?></p>
                <a href="news-details.php?slug=<?= $item['slug'] ?>" class="btn btn-sm btn-secondary">
                    Read More <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>