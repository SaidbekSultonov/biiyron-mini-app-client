<?php
header('Content-Type: application/json');
require_once '../bot/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;
$lang    = $data['lang']    ?? null;

if (!$user_id || !$lang) {
    echo json_encode(['success' => false, 'error' => 'Missing params']);
    exit;
}

$lang_int = match($lang) {
    'uz'    => 1,
    'ru'    => 2,
    'kr'    => 3,
    default => 1,
};

$stmt = $conn->prepare("UPDATE clients SET language = ? WHERE chat_id = ?");
$stmt->execute([$lang_int, $user_id]);

echo json_encode(['success' => true]);
