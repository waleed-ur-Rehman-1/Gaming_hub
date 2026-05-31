<?php
require_once '../includes/db.php';
if (!isAdmin()) redirect('../index.php');

$pageTitle = 'Game Requests';

// Handle status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reqId = $_GET['id'];
    $action = $_GET['action'];
    
    if (in_array($action, ['approved', 'rejected', 'pending'])) {
        $pdo->prepare("UPDATE game_requests SET status = ? WHERE request_id = ?")->execute([$action, $reqId]);
        flashMessage('success', 'Request status updated to ' . $action . '.');
    } elseif ($action == 'delete') {
        $pdo->prepare("DELETE FROM game_requests WHERE request_id = ?")->execute([$reqId]);
        flashMessage('success', 'Request deleted.');
    }
    redirect('requests.php');
}

$filter = $_GET['filter'] ?? 'all';
$where = "1=1";
if ($filter == 'pending') $where = "status = 'pending'";
elseif ($filter == 'approved') $where = "status = 'approved'";
elseif ($filter == 'rejected') $where = "status = 'rejected'";

$requests = $pdo->query("SELECT r.*, u.username, u.full_name as user_name 
    FROM game_requests r 
    LEFT JOIN users u ON r.user_id = u.user_id 
    WHERE $where 
    ORDER BY r.created_at DESC")->fetchAll();

require_once 'admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Game Requests</h2>
    <div style="display: flex; gap: 10px;">
        <a href="requests.php?filter=all" class="btn btn-sm <?= $filter == 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
        <a href="requests.php?filter=pending" class="btn btn-sm <?= $filter == 'pending' ? 'btn-primary' : 'btn-secondary' ?>">Pending</a>
        <a href="requests.php?filter=approved" class="btn btn-sm <?= $filter == 'approved' ? 'btn-primary' : 'btn-secondary' ?>">Approved</a>
        <a href="requests.php?filter=rejected" class="btn btn-sm <?= $filter == 'rejected' ? 'btn-primary' : 'btn-secondary' ?>">Rejected</a>
    </div>
</div>

<div style="background: var(--secondary-black); border-radius: 16px; padding: 25px; border: 1px solid var(--border-color);">
    <?php if (empty($requests)): ?>
    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
        <i class="fas fa-plus-circle" style="font-size: 3rem; margin-bottom: 15px;"></i>
        <p>No requests found.</p>
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Requester</th>
                <th>Email</th>
                <th>Game</th>
                <th>Description</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
            <tr>
                <td>
                    <strong><?= sanitizeInput($req['name']) ?></strong>
                    <?php if ($req['user_name']): ?>
                    <br><small style="color: var(--text-muted);">User: <?= sanitizeInput($req['user_name']) ?></small>
                    <?php endif; ?>
                </td>
                <td><a href="mailto:<?= sanitizeInput($req['email']) ?>"><?= sanitizeInput($req['email']) ?></a></td>
                <td><strong style="color: var(--neon-blue);"><?= sanitizeInput($req['requested_game']) ?></strong></td>
                <td><?= truncateText(sanitizeInput($req['description'] ?? ''), 60) ?></td>
                <td>
                    <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: <?= $req['status'] == 'approved' ? 'rgba(57,255,20,0.15); color: #39FF14;' : ($req['status'] == 'rejected' ? 'rgba(255,0,110,0.15); color: #FF006E;' : 'rgba(255,193,7,0.15); color: #FFC107;') ?>">
                        <?= ucfirst($req['status']) ?>
                    </span>
                </td>
                <td><?= formatDate($req['created_at']) ?></td>
                <td>
                    <div class="table-actions">
                        <?php if ($req['status'] != 'approved'): ?>
                        <a href="requests.php?action=approved&id=<?= $req['request_id'] ?>" class="btn-icon btn-edit" title="Approve" style="background: rgba(57,255,20,0.15); color: #39FF14;"><i class="fas fa-check"></i></a>
                        <?php endif; ?>
                        <?php if ($req['status'] != 'rejected'): ?>
                        <a href="requests.php?action=rejected&id=<?= $req['request_id'] ?>" class="btn-icon btn-edit" title="Reject"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                        <a href="requests.php?action=delete&id=<?= $req['request_id'] ?>" class="btn-icon btn-delete" onclick="return confirm('Delete this request?')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'admin-footer.php'; ?>