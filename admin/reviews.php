<?php
require_once '../includes/db.php';
if (!isAdmin()) redirect('../index.php');

$pageTitle = 'Review Management';

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reviewId = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE review_id = ?")->execute([$reviewId]);
        flashMessage('success', 'Review approved.');
    } elseif ($action == 'reject') {
        $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE review_id = ?")->execute([$reviewId]);
        flashMessage('success', 'Review rejected.');
    } elseif ($action == 'delete') {
        $pdo->prepare("DELETE FROM reviews WHERE review_id = ?")->execute([$reviewId]);
        flashMessage('success', 'Review deleted.');
    }
    redirect('reviews.php');
}

$filter = $_GET['filter'] ?? 'all';
$where = "1=1";
$params = [];

if ($filter == 'pending') $where = "r.status = 'pending'";
elseif ($filter == 'approved') $where = "r.status = 'approved'";
elseif ($filter == 'rejected') $where = "r.status = 'rejected'";

$reviews = $pdo->prepare("SELECT r.*, u.full_name, u.username, u.avatar, g.title as game_title, g.slug as game_slug 
    FROM reviews r 
    JOIN users u ON r.user_id = u.user_id 
    JOIN games g ON r.game_id = g.game_id 
    WHERE $where 
    ORDER BY r.created_at DESC");
$reviews->execute($params);
$reviews = $reviews->fetchAll();

require_once 'admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Reviews</h2>
    <div style="display: flex; gap: 10px;">
        <a href="reviews.php?filter=all" class="btn btn-sm <?= $filter == 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
        <a href="reviews.php?filter=pending" class="btn btn-sm <?= $filter == 'pending' ? 'btn-primary' : 'btn-secondary' ?>">Pending</a>
        <a href="reviews.php?filter=approved" class="btn btn-sm <?= $filter == 'approved' ? 'btn-primary' : 'btn-secondary' ?>">Approved</a>
        <a href="reviews.php?filter=rejected" class="btn btn-sm <?= $filter == 'rejected' ? 'btn-primary' : 'btn-secondary' ?>">Rejected</a>
    </div>
</div>

<div style="background: var(--secondary-black); border-radius: 16px; padding: 25px; border: 1px solid var(--border-color);">
    <?php if (empty($reviews)): ?>
    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
        <i class="fas fa-star" style="font-size: 3rem; margin-bottom: 15px;"></i>
        <p>No reviews found in this category.</p>
    </div>
    <?php else: ?>
    <?php foreach ($reviews as $review): ?>
    <div class="review-card" style="margin-bottom: 20px;">
        <div class="review-header">
            <div class="review-user">
                <img src="../uploads/avatars/<?= $review['avatar'] ?? 'default-avatar.jpg' ?>" class="review-avatar">
                <div>
                    <div style="font-weight: 600;"><?= sanitizeInput($review['full_name']) ?> <span style="color: var(--text-muted); font-weight: 400;">(@<?= sanitizeInput($review['username']) ?>)</span></div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        on <a href="../game-details.php?slug=<?= $review['game_slug'] ?>" target="_blank" style="color: var(--neon-blue);"><?= sanitizeInput($review['game_title']) ?></a> 
                        • <?= formatDate($review['created_at']) ?>
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="review-stars" style="font-size: 1.1rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" style="<?= $i > $review['rating'] ? 'opacity: 0.3;' : '' ?>"></i>
                    <?php endfor; ?>
                </div>
                <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: <?= $review['status'] == 'approved' ? 'rgba(57,255,20,0.15); color: #39FF14;' : ($review['status'] == 'rejected' ? 'rgba(255,0,110,0.15); color: #FF006E;' : 'rgba(255,193,7,0.15); color: #FFC107;') ?>"><?= ucfirst($review['status']) ?></span>
            </div>
        </div>
        <div class="review-text" style="margin-bottom: 15px;">
            <?= nl2br(sanitizeInput($review['review_text'])) ?>
        </div>
        <div style="display: flex; gap: 10px;">
            <?php if ($review['status'] != 'approved'): ?>
            <a href="reviews.php?action=approve&id=<?= $review['review_id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Approve</a>
            <?php endif; ?>
            <?php if ($review['status'] != 'rejected'): ?>
            <a href="reviews.php?action=reject&id=<?= $review['review_id'] ?>" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i> Reject</a>
            <?php endif; ?>
            <a href="reviews.php?action=delete&id=<?= $review['review_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this review?')"><i class="fas fa-trash"></i> Delete</a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'admin-footer.php'; ?>