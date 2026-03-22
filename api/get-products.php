<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Faqat GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// user_id tekshiruvi
$userId = $_GET['user_id'] ?? null;
$categoryId = $_GET['category_id'] ?? null;

if (!$userId || !ctype_digit($userId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user_id']);
    exit;
}

if ($categoryId !== null && !ctype_digit($categoryId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid category_id']);
    exit;
}

// DB ulanish
require_once __DIR__ . '/../config.php';
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

/**
 * 1. Mijoz tilini va oxirgi shop warehouse_id sini aniqlash
 */
$stmt = $pdo->prepare("
    SELECT c.language, s.warehouse_id
    FROM clients c
    LEFT JOIN shops s ON s.client_id = c.id AND s.status = 1
    WHERE c.chat_id = :chat_id
    ORDER BY s.id DESC
    LIMIT 1
");
$stmt->execute(['chat_id' => $userId]);
$client = $stmt->fetch();

$warehouseId = $client['warehouse_id'] ?? null;

$language = (int)($client['language'] ?? 1);

// Til ustunlari
switch ($language) {
    case 2:
        $nameColumn = 'name_ru';
        break;
    case 3:
        $nameColumn = 'name_kr';
        break;
    default:
        $nameColumn = 'name_uz';
}

/**
 * 2. Products query
 *  - faqat aktiv
 *  - qoldig’i bor (warehouse_id bo’yicha filter)
 *  - category bo’yicha filter ixtiyoriy
 */
$sql = "
    SELECT
        p.id,
        p.{$nameColumn} AS name,
        p.image,
        p.price
    FROM products p
    WHERE p.is_active = 1
";

$params = [];

// Warehouse bo’yicha stock filter
if ($warehouseId) {
    $sql .= "
      AND EXISTS (
          SELECT 1 FROM batches b
          WHERE b.product_id = p.id
            AND b.warehouse_id = :warehouse_id
            AND b.deleted_at IS NULL
            AND (CASE WHEN p.type = 1 THEN b.count ELSE b.weight END) > 0
      )
    ";
    $params[‘warehouse_id’] = $warehouseId;
} else {
    $sql .= " AND p.stock > 0";
}

if ($categoryId) {
    $sql .= " AND p.category_id = :category_id";
    $params[‘category_id’] = $categoryId;
}

$sql .= " ORDER BY p.id DESC LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll();

/**
 * 3. Javob
 */
echo json_encode([
    'success' => true,
    'products' => $products
], JSON_UNESCAPED_UNICODE);
