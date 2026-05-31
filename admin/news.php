<?php
require_once '../includes/db.php';
if (!isAdmin()) redirect('../index.php');

$pageTitle = 'News Management';

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $newsId = $_GET['delete'];
    $img = $pdo->prepare("SELECT featured_image FROM news WHERE news_id = ?");
    $img->execute([$newsId]);
    $imgPath = $img->fetchColumn();
    @unlink('../uploads/news/' . $imgPath);
    
    $pdo->prepare("DELETE FROM news WHERE news_id = ?")->execute([$newsId]);
    flashMessage('success', 'News article deleted.');
    redirect('news.php');
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newsId = $_POST['news_id'] ?? 0;
    $title = sanitizeInput($_POST['title'] ?? '');
    $slug = generateSlug($title);
    $content = $_POST['content'] ?? '';
    $excerpt = sanitizeInput($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    
    $featuredImage = '';
    if (!empty($_FILES['featured_image']['name'])) {
        $featuredImage = uploadFile($_FILES['featured_image'], '../uploads/news/');
    }
    
    if ($newsId > 0) {
        $sql = "UPDATE news SET title=?, slug=?, content=?, excerpt=?, status=?";
        $params = [$title, $slug, $content, $excerpt, $status];
        if ($featuredImage) {
            $sql .= ", featured_image=?";
            $params[] = $featuredImage;
        }
        $sql .= " WHERE news_id=?";
        $params[] = $newsId;
        $pdo->prepare($sql)->execute($params);
        flashMessage('success', 'News updated.');
    } else {
        if (empty($featuredImage)) {
            $featuredImage = 'default-news.jpg';
        }
        $pdo->prepare("INSERT INTO news (title, slug, content, excerpt, featured_image, author_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$title, $slug, $content, $excerpt, $featuredImage, getUserId(), $status]);
        flashMessage('success', 'News published.');
    }
    redirect('news.php');
}

$news = $pdo->query("SELECT n.*, u.full_name as author 
    FROM news n 
    JOIN users u ON n.author_id = u.user_id 
    ORDER BY n.created_at DESC")->fetchAll();

$editNews = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE news_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editNews = $stmt->fetch();
}

require_once 'admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2><?= isset($editNews) ? 'Edit News' : 'All News' ?></h2>
    <?php if (!isset($editNews)): ?>
    <button onclick="document.getElementById('newsModal').classList.add('active')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add News
    </button>
    <?php endif; ?>
</div>

<?php if (isset($editNews)): ?>
<div style="background: var(--secondary-black); border-radius: 16px; padding: 30px; border: 1px solid var(--border-color); margin-bottom: 30px;">
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="news_id" value="<?= $editNews['news_id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" required value="<?= sanitizeInput($editNews['title']) ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Excerpt</label>
            <input type="text" name="excerpt" class="form-control" maxlength="500" value="<?= sanitizeInput($editNews['excerpt'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Content *</label>
            <textarea name="content" class="form-control" rows="8" required><?= sanitizeInput($editNews['content']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Featured Image (leave empty to keep current)</label>
            <input type="file" name="featured_image" class="form-control" accept="image/*">
            <?php if ($editNews['featured_image']): ?>
            <img src="../uploads/news/<?= $editNews['featured_image'] ?>" style="width: 100px; margin-top: 10px; border-radius: 8px;">
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="published" <?= $editNews['status'] == 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= $editNews['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 15px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            <a href="news.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div style="background: var(--secondary-black); border-radius: 16px; padding: 25px; border: 1px solid var(--border-color); overflow-x: auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Author</th>
                <th>Status</th>
                <th>Views</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($news as $item): ?>
            <tr>
                <td><img src="../uploads/news/<?= $item['featured_image'] ?? 'default-news.jpg' ?>" style="width: 60px; height: 45px; object-fit: cover; border-radius: 6px;"></td>
                <td><strong><?= sanitizeInput($item['title']) ?></strong></td>
                <td><?= sanitizeInput($item['author']) ?></td>
                <td><span style="padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: <?= $item['status'] == 'published' ? 'rgba(57,255,20,0.15); color: #39FF14;' : 'rgba(255,193,7,0.15); color: #FFC107;' ?>"><?= ucfirst($item['status']) ?></span></td>
                <td><?= number_format($item['view_count']) ?></td>
                <td><?= formatDate($item['created_at']) ?></td>
                <td>
                    <div class="table-actions">
                        <a href="../news-details.php?slug=<?= $item['slug'] ?>" target="_blank" class="btn-icon btn-edit" title="View"><i class="fas fa-eye"></i></a>
                        <a href="news.php?edit=<?= $item['news_id'] ?>" class="btn-icon btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="news.php?delete=<?= $item['news_id'] ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Delete this article?')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="newsModal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle" style="color: var(--neon-blue);"></i> Add News Article</h3>
            <button class="modal-close" onclick="document.getElementById('newsModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Excerpt</label>
                <input type="text" name="excerpt" class="form-control" maxlength="500" placeholder="Short summary...">
            </div>
            
            <div class="form-group">
                <label class="form-label">Content *</label>
                <textarea name="content" class="form-control" rows="6" required></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Featured Image</label>
                <input type="file" name="featured_image" class="form-control" accept="image/*">
            </div>
            
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-paper-plane"></i> Publish</button>
        </form>
    </div>
</div>

<?php require_once 'admin-footer.php'; ?>