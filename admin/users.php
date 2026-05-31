<?php
require_once '../includes/db.php';
if (!isAdmin()) redirect('../index.php');

$pageTitle = 'User Management';

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = $_GET['delete'];
    // Prevent deleting self
    if ($userId == getUserId()) {
        flashMessage('danger', 'You cannot delete your own account.');
    } else {
        $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'user'")->execute([$userId]);
        flashMessage('success', 'User deleted.');
    }
    redirect('users.php');
}

// Ban/Unban
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $userId = $_GET['toggle'];
    $current = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
    $current->execute([$userId]);
    $status = $current->fetchColumn();
    $newStatus = $status == 'active' ? 'banned' : 'active';
    $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?")->execute([$newStatus, $userId]);
    flashMessage('success', 'User status updated to ' . $newStatus . '.');
    redirect('users.php');
}

$users = $pdo->query("SELECT u.*, 
    (SELECT COUNT(*) FROM downloads WHERE user_id = u.user_id) as total_downloads,
    (SELECT COUNT(*) FROM reviews WHERE user_id = u.user_id) as total_reviews,
    (SELECT COUNT(*) FROM wishlist WHERE user_id = u.user_id) as total_wishlist
    FROM users u 
    WHERE u.role = 'user' 
    ORDER BY u.created_at DESC")->fetchAll();

require_once 'admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Users</h2>
    <span style="color: var(--text-secondary);"><?= count($users) ?> total users</span>
</div>

<div style="background: var(--secondary-black); border-radius: 16px; padding: 25px; border: 1px solid var(--border-color); overflow-x: auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Downloads</th>
                <th>Reviews</th>
                <th>Wishlist</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <img src="../uploads/avatars/<?= $user['avatar'] ?? 'default-avatar.jpg' ?>" style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--border-color);">
                        <div>
                            <div style="font-weight: 600;"><?= sanitizeInput($user['full_name']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">@<?= sanitizeInput($user['username']) ?></div>
                        </div>
                    </div>
                </td>
                <td><?= sanitizeInput($user['email']) ?></td>
                <td><?= number_format($user['total_downloads']) ?></td>
                <td><?= number_format($user['total_reviews']) ?></td>
                <td><?= number_format($user['total_wishlist']) ?></td>
                <td>
                    <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: <?= $user['status'] == 'active' ? 'rgba(57,255,20,0.15); color: #39FF14;' : 'rgba(255,0,110,0.15); color: #FF006E;' ?>">
                        <?= ucfirst($user['status']) ?>
                    </span>
                </td>
                <td><?= formatDate($user['created_at']) ?></td>
                <td>
                    <div class="table-actions">
                        <a href="users.php?toggle=<?= $user['user_id'] ?>" class="btn-icon btn-edit" title="<?= $user['status'] == 'active' ? 'Ban' : 'Unban' ?>">
                            <i class="fas fa-<?= $user['status'] == 'active' ? 'ban' : 'check-circle' ?>"></i>
                        </a>
                        <a href="users.php?delete=<?= $user['user_id'] ?>" class="btn-icon btn-delete" onclick="return confirm('Delete this user? All their data will be removed.')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'admin-footer.php'; ?>