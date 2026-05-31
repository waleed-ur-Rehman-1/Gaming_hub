<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$gameId = $data['game_id'] ?? 0;
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!validateCSRFToken($csrfToken)) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $pdo->prepare("INSERT INTO downloads (user_id, game_id, ip_address) VALUES (?, ?, ?)")
        ->execute([getUserId(), $gameId, $_SERVER['REMOTE_ADDR']]);
    
    $pdo->prepare("UPDATE games SET download_count = download_count + 1 WHERE game_id = ?")
        ->execute([$gameId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
?>