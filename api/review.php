<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$gameId = $data['game_id'] ?? 0;
$rating = $data['rating'] ?? 0;
$reviewText = sanitizeInput($data['review_text'] ?? '');
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!validateCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

if ($rating < 1 || $rating > 5 || empty($reviewText)) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating or review text']);
    exit;
}

try {
    // Check if already reviewed
    $stmt = $pdo->prepare("SELECT review_id FROM reviews WHERE user_id = ? AND game_id = ?");
    $stmt->execute([getUserId(), $gameId]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already reviewed this game']);
        exit;
    }
    
    $pdo->prepare("INSERT INTO reviews (user_id, game_id, rating, review_text, status) VALUES (?, ?, ?, ?, 'approved')")
        ->execute([getUserId(), $gameId, $rating, $reviewText]);
    
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>