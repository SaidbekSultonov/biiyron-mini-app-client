<?php
header('Content-Type: application/json');
require_once '../bot/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;
$shop_id  = $data['shop_id']  ?? null;

if (!$user_id || !$shop_id) {
    echo json_encode(['success' => false, 'error' => 'Missing params']);
    exit;
}

// Verify shop belongs to this client and is active
$stmt = $conn->prepare("
    SELECT s.warehouse_id
    FROM shops s
    INNER JOIN clients c ON c.id = s.client_id
    WHERE s.id = ? AND c.chat_id = ? AND s.status = 1
    LIMIT 1
");
$stmt->execute([$shop_id, $user_id]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    echo json_encode(['success' => false, 'error' => 'Shop not found']);
    exit;
}

$upd = $conn->prepare("UPDATE clients SET temp_warehouse_id = ? WHERE chat_id = ?");
$upd->execute([$shop['warehouse_id'], $user_id]);

echo json_encode(['success' => true]);
