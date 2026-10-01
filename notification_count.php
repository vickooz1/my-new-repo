<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = initializeDatabase();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
$stmt->execute([$_SESSION['user_id']]);
$count = (int) $stmt->fetchColumn();
echo json_encode(['unread' => $count]);
