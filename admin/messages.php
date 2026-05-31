<?php
require_once '../includes/db.php';
if (!isAdmin()) redirect('../index.php');

$pageTitle = 'Contact Messages';

// Mark as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE message_id = ?")->execute([$_GET['read']]);
    redirect('messages.php');
}

// Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM contact_messages WHERE message_id = ?")->execute([$_GET['delete']]);
    flashMessage('success', 'Message deleted.');
    redirect('messages.php');
}

$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();

require_once 'admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Contact Messages</h2>
    <span style="color: var(--text-secondary);"><?= count(array_filter($messages, fn($m) => $m['status'] == 'unread')) ?> unread</span>
</div>

<div style="background: var(--secondary-black); border-radius: 16px; padding: 25px; border: 1px solid var(--border-color);">
    <?php if (empty($messages)): ?>
    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
        <i class="fas fa-envelope-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
        <p>No messages yet.</p>
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Status</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr style="<?= $msg['status'] == 'unread' ? 'background: rgba(0,212,255,0.05);' : '' ?>">
                <td>
                    <?php if ($msg['status'] == 'unread'): ?>
                    <span style="width: 10px; height: 10px; background: var(--neon-blue); border-radius: 50%; display: inline-block;"></span>
                    <?php else: ?>
                    <span style="width: 10px; height: 10px; background: var(--text-muted); border-radius: 50%; display: inline-block;"></span>
                    <?php endif; ?>
                </td>
                <td><strong><?= sanitizeInput($msg['name']) ?></strong></td>
                <td><a href="mailto:<?= sanitizeInput($msg['email']) ?>"><?= sanitizeInput($msg['email']) ?></a></td>
                <td><?= sanitizeInput($msg['subject']) ?></td>
                <td><?= truncateText(sanitizeInput($msg['message']), 80) ?></td>
                <td><?= formatDate($msg['created_at']) ?></td>
                <td>
                    <div class="table-actions">
                        <?php if ($msg['status'] == 'unread'): ?>
                        <a href="messages.php?read=<?= $msg['message_id'] ?>" class="btn-icon btn-edit" title="Mark as Read"><i class="fas fa-check"></i></a>
                        <?php endif; ?>
                        <a href="mailto:<?= sanitizeInput($msg['email']) ?>?subject=Re: <?= urlencode($msg['subject']) ?>" class="btn-icon btn-edit" title="Reply" style="background: rgba(57,255,20,0.15); color: #39FF14;"><i class="fas fa-reply"></i></a>
                        <a href="messages.php?delete=<?= $msg['message_id'] ?>" class="btn-icon btn-delete" onclick="return confirm('Delete this message?')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'admin-footer.php'; ?>