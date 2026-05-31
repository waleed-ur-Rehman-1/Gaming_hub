<?php
$pageTitle = 'Request Game';
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $gameName = sanitizeInput($_POST['game_name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $userId = getUserId();
    
    $stmt = $pdo->prepare("INSERT INTO game_requests (user_id, name, email, requested_game, description) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$userId, $name, $email, $gameName, $description])) {
        flashMessage('success', 'Game request submitted! We will review it shortly.');
    } else {
        flashMessage('danger', 'Failed to submit request.');
    }
}

require_once 'includes/header.php';
?>

<section class="section" style="padding-top: 100px;">
    <div style="max-width: 600px; margin: 0 auto;">
        <div class="section-header">
            <h2><i class="fas fa-plus-circle" style="color: var(--neon-purple);"></i> Request a Game</h2>
            <p>Can't find your favorite game? Let us know!</p>
            <div class="section-line"></div>
        </div>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Your Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= isLoggedIn() ? sanitizeInput($_SESSION['user_name']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= isLoggedIn() ? sanitizeInput($_SESSION['user_email']) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Game Name</label>
                    <input type="text" name="game_name" class="form-control" required placeholder="Enter the game you want">
                </div>
                <div class="form-group">
                    <label class="form-label">Additional Details (Optional)</label>
                    <textarea name="description" class="form-control" placeholder="Platform, version, or any other details..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>