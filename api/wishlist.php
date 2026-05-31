<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$gameId = $data['game_id'] ?? 0;
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!validateCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND game_id = ?");
    $stmt->execute([getUserId(), $gameId]);
    
    if ($stmt->fetch()) {
        // Remove from wishlist
        $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND game_id = ?")->execute([getUserId(), $gameId]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist']);
    } else {
        // Add to wishlist
        $pdo->prepare("INSERT INTO wishlist (user_id, game_id) VALUES (?, ?)")->execute([getUserId(), $gameId]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to wishlist']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>