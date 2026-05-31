<?php
require_once '../includes/db.php';
if (!isAdmin()) redirect('../index.php');

$pageTitle = 'Game Management';

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $gameId = $_GET['delete'];
    
    // Delete screenshots first
    $shots = $pdo->prepare("SELECT image_path FROM screenshots WHERE game_id = ?");
    $shots->execute([$gameId]);
    foreach ($shots->fetchAll() as $shot) {
        @unlink('../uploads/screenshots/' . $shot['image_path']);
    }
    
    // Delete cover image
    $cover = $pdo->prepare("SELECT cover_image FROM games WHERE game_id = ?");
    $cover->execute([$gameId]);
    $coverImg = $cover->fetchColumn();
    @unlink('../uploads/games/' . $coverImg);
    
    // Delete from DB (cascade will handle screenshots, reviews, wishlist, downloads)
    $pdo->prepare("DELETE FROM games WHERE game_id = ?")->execute([$gameId]);
    flashMessage('success', 'Game deleted successfully.');
    redirect('games.php');
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gameId = $_POST['game_id'] ?? 0;
    $title = sanitizeInput($_POST['title'] ?? '');
    $slug = generateSlug($title);
    $description = $_POST['description'] ?? '';
    $shortDesc = sanitizeInput($_POST['short_description'] ?? '');
    $categoryId = $_POST['category_id'] ?? 0;
    $publisher = sanitizeInput($_POST['publisher'] ?? '');
    $developer = sanitizeInput($_POST['developer'] ?? '');
    $releaseDate = $_POST['release_date'] ?? date('Y-m-d');
    $trailerUrl = sanitizeInput($_POST['trailer_url'] ?? '');
    $downloadLink = sanitizeInput($_POST['download_link'] ?? '');
    $fileSize = sanitizeInput($_POST['file_size'] ?? '');
    $version = sanitizeInput($_POST['version'] ?? '1.0');
    $platform = sanitizeInput($_POST['platform'] ?? 'Windows');
    $systemReq = $_POST['system_requirements'] ?? '';
    $featured = isset($_POST['featured']) ? 1 : 0;
    $trending = isset($_POST['trending']) ? 1 : 0;
    $newRelease = isset($_POST['new_release']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';
    
    // Handle cover image upload
    $coverImage = '';
    if (!empty($_FILES['cover_image']['name'])) {
        $coverImage = uploadFile($_FILES['cover_image'], '../uploads/games/');
        if (!$coverImage) {
            flashMessage('danger', 'Failed to upload cover image.');
            redirect('games.php');
        }
    }
    
    if ($gameId > 0) {
        // Update existing
        $sql = "UPDATE games SET title=?, slug=?, description=?, short_description=?, category_id=?, publisher=?, developer=?, release_date=?, trailer_url=?, download_link=?, file_size=?, version=?, platform=?, system_requirements=?, featured=?, trending=?, new_release=?, status=?";
        $params = [$title, $slug, $description, $shortDesc, $categoryId, $publisher, $developer, $releaseDate, $trailerUrl, $downloadLink, $fileSize, $version, $platform, $systemReq, $featured, $trending, $newRelease, $status];
        
        if ($coverImage) {
            $sql .= ", cover_image=?";
            $params[] = $coverImage;
        }
        
        $sql .= " WHERE game_id=?";
        $params[] = $gameId;
        
        $pdo->prepare($sql)->execute($params);
        
        // Handle new screenshots
        if (!empty($_FILES['screenshots']['name'][0])) {
            foreach ($_FILES['screenshots']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['screenshots']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['screenshots']['name'][$key],
                        'type' => $_FILES['screenshots']['type'][$key],
                        'tmp_name' => $tmpName,
                        'error' => $_FILES['screenshots']['error'][$key],
                        'size' => $_FILES['screenshots']['size'][$key]
                    ];
                    $imgPath = uploadFile($file, '../uploads/screenshots/');
                    if ($imgPath) {
                        $pdo->prepare("INSERT INTO screenshots (game_id, image_path, sort_order) VALUES (?, ?, ?)")
                            ->execute([$gameId, $imgPath, $key]);
                    }
                }
            }
        }
        
        flashMessage('success', 'Game updated successfully.');
    } else {
        // Insert new
        if (empty($coverImage)) {
            flashMessage('danger', 'Cover image is required.');
            redirect('games.php');
        }
        
        $pdo->prepare("INSERT INTO games (title, slug, description, short_description, category_id, publisher, developer, release_date, trailer_url, download_link, cover_image, file_size, version, platform, system_requirements, featured, trending, new_release, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$title, $slug, $description, $shortDesc, $categoryId, $publisher, $developer, $releaseDate, $trailerUrl, $downloadLink, $coverImage, $fileSize, $version, $platform, $systemReq, $featured, $trending, $newRelease, $status]);
        
        $newGameId = $pdo->lastInsertId();
        
        // Handle screenshots
        if (!empty($_FILES['screenshots']['name'][0])) {
            foreach ($_FILES['screenshots']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['screenshots']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['screenshots']['name'][$key],
                        'type' => $_FILES['screenshots']['type'][$key],
                        'tmp_name' => $tmpName,
                        'error' => $_FILES['screenshots']['error'][$key],
                        'size' => $_FILES['screenshots']['size'][$key]
                    ];
                    $imgPath = uploadFile($file, '../uploads/screenshots/');
                    if ($imgPath) {
                        $pdo->prepare("INSERT INTO screenshots (game_id, image_path, sort_order) VALUES (?, ?, ?)")
                            ->execute([$newGameId, $imgPath, $key]);
                    }
                }
            }
        }
        
        flashMessage('success', 'Game added successfully.');
    }
    redirect('games.php');
}

// Get all games
$games = $pdo->query("SELECT g.*, c.category_name,
    (SELECT COUNT(*) FROM reviews WHERE game_id = g.game_id) as review_count,
    (SELECT COUNT(*) FROM downloads WHERE game_id = g.game_id) as dl_count
    FROM games g 
    JOIN categories c ON g.category_id = c.category_id 
    ORDER BY g.created_at DESC")->fetchAll();

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

// Get game to edit if specified
$editGame = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM games WHERE game_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editGame = $stmt->fetch();
    
    $editScreenshots = $pdo->prepare("SELECT * FROM screenshots WHERE game_id = ? ORDER BY sort_order");
    $editScreenshots->execute([$_GET['edit']]);
    $editScreenshots = $editScreenshots->fetchAll();
}

require_once 'admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2><?= isset($editGame) ? 'Edit Game' : 'All Games' ?></h2>
    <?php if (!isset($editGame)): ?>
    <button onclick="document.getElementById('gameModal').classList.add('active')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Game
    </button>
    <?php endif; ?>
</div>

<?php if (isset($editGame)): ?>
<!-- Edit Form -->
<div style="background: var(--secondary-black); border-radius: 16px; padding: 30px; border: 1px solid var(--border-color); margin-bottom: 30px;">
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="game_id" value="<?= $editGame['game_id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Game Title *</label>
                <input type="text" name="title" class="form-control" required value="<?= sanitizeInput($editGame['title']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Category *</label>
                <select name="category_id" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>" <?= $editGame['category_id'] == $cat['category_id'] ? 'selected' : '' ?>><?= $cat['category_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Publisher *</label>
                <input type="text" name="publisher" class="form-control" required value="<?= sanitizeInput($editGame['publisher']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Developer</label>
                <input type="text" name="developer" class="form-control" value="<?= sanitizeInput($editGame['developer'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Release Date *</label>
                <input type="date" name="release_date" class="form-control" required value="<?= $editGame['release_date'] ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Short Description</label>
            <input type="text" name="short_description" class="form-control" maxlength="500" value="<?= sanitizeInput($editGame['short_description'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Full Description *</label>
            <textarea name="description" class="form-control" rows="5" required><?= sanitizeInput($editGame['description']) ?></textarea>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Trailer URL (YouTube embed)</label>
                <input type="url" name="trailer_url" class="form-control" value="<?= sanitizeInput($editGame['trailer_url'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Download Link</label>
                <input type="url" name="download_link" class="form-control" value="<?= sanitizeInput($editGame['download_link'] ?? '') ?>">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">File Size</label>
                <input type="text" name="file_size" class="form-control" placeholder="e.g. 15 GB" value="<?= sanitizeInput($editGame['file_size'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Version</label>
                <input type="text" name="version" class="form-control" value="<?= sanitizeInput($editGame['version']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Platform</label>
                <input type="text" name="platform" class="form-control" value="<?= sanitizeInput($editGame['platform'] ?? 'Windows') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">System Requirements</label>
            <textarea name="system_requirements" class="form-control" rows="3" placeholder="OS, Processor, Memory, Graphics, Storage..."><?= sanitizeInput($editGame['system_requirements'] ?? '') ?></textarea>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Cover Image (leave empty to keep current)</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*">
                <?php if ($editGame['cover_image']): ?>
                <div style="margin-top: 10px;">
                    <img src="../uploads/games/<?= $editGame['cover_image'] ?>" style="width: 100px; border-radius: 8px; border: 2px solid var(--neon-blue);">
                </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">New Screenshots (multiple)</label>
                <input type="file" name="screenshots[]" class="form-control" accept="image/*" multiple>
                <?php if (!empty($editScreenshots)): ?>
                <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php foreach ($editScreenshots as $shot): ?>
                    <img src="../uploads/screenshots/<?= $shot['image_path'] ?>" style="width: 80px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="display: flex; gap: 20px; margin: 20px 0;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="featured" value="1" <?= $editGame['featured'] ? 'checked' : '' ?>> Featured
            </label>
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="trending" value="1" <?= $editGame['trending'] ? 'checked' : '' ?>> Trending
            </label>
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="new_release" value="1" <?= $editGame['new_release'] ? 'checked' : '' ?>> New Release
            </label>
        </div>
        
        <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="active" <?= $editGame['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $editGame['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 15px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Game</button>
            <a href="games.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Games Table -->
<div style="background: var(--secondary-black); border-radius: 16px; padding: 25px; border: 1px solid var(--border-color); overflow-x: auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Cover</th>
                <th>Title</th>
                <th>Category</th>
                <th>Downloads</th>
                <th>Reviews</th>
                <th>Status</th>
                <th>Featured</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($games as $game): ?>
            <tr>
                <td><img src="../uploads/games/<?= $game['cover_image'] ?>" style="width: 50px; height: 70px; object-fit: cover; border-radius: 6px;"></td>
                <td><strong><?= sanitizeInput($game['title']) ?></strong><br><small style="color: var(--text-muted);"><?= formatDate($game['release_date']) ?></small></td>
                <td><?= $game['category_name'] ?></td>
                <td><?= number_format($game['dl_count']) ?></td>
                <td><?= number_format($game['review_count']) ?></td>
                <td><span style="padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: <?= $game['status'] == 'active' ? 'rgba(57,255,20,0.15); color: #39FF14;' : 'rgba(255,0,110,0.15); color: #FF006E;' ?>"><?= ucfirst($game['status']) ?></span></td>
                <td>
                    <?php if ($game['featured']): ?><span style="color: var(--neon-blue);"><i class="fas fa-check"></i></span><?php else: ?><span style="color: var(--text-muted);">-</span><?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="../game-details.php?slug=<?= $game['slug'] ?>" target="_blank" class="btn-icon btn-edit" title="View"><i class="fas fa-eye"></i></a>
                        <a href="games.php?edit=<?= $game['game_id'] ?>" class="btn-icon btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="games.php?delete=<?= $game['game_id'] ?>" class="btn-icon btn-delete confirm-delete" title="Delete" onclick="return confirm('Delete this game? This cannot be undone!')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Game Modal -->
<div class="modal-overlay" id="gameModal">
    <div class="modal" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle" style="color: var(--neon-blue);"></i> Add New Game</h3>
            <button class="modal-close" onclick="document.getElementById('gameModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Game Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>"><?= $cat['category_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Publisher *</label>
                    <input type="text" name="publisher" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Developer</label>
                    <input type="text" name="developer" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Release Date *</label>
                    <input type="date" name="release_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Short Description</label>
                <input type="text" name="short_description" class="form-control" maxlength="500" placeholder="Brief summary for cards...">
            </div>
            
            <div class="form-group">
                <label class="form-label">Full Description *</label>
                <textarea name="description" class="form-control" rows="4" required></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Trailer URL (YouTube)</label>
                    <input type="url" name="trailer_url" class="form-control" placeholder="https://youtube.com/watch?v=...">
                </div>
                <div class="form-group">
                    <label class="form-label">Download Link</label>
                    <input type="url" name="download_link" class="form-control">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">File Size</label>
                    <input type="text" name="file_size" class="form-control" placeholder="e.g. 15 GB">
                </div>
                <div class="form-group">
                    <label class="form-label">Version</label>
                    <input type="text" name="version" class="form-control" value="1.0">
                </div>
                <div class="form-group">
                    <label class="form-label">Platform</label>
                    <input type="text" name="platform" class="form-control" value="Windows">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">System Requirements</label>
                <textarea name="system_requirements" class="form-control" rows="3" placeholder="OS, Processor, Memory, Graphics, Storage..."></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Cover Image *</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Screenshots (multiple)</label>
                    <input type="file" name="screenshots[]" class="form-control" accept="image/*" multiple>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px; margin: 20px 0;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="featured" value="1"> Featured
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trending" value="1"> Trending
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="new_release" value="1" checked> New Release
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-plus"></i> Add Game</button>
        </form>
    </div>
</div>

<?php require_once 'admin-footer.php'; ?>