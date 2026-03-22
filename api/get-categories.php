<?php
declare(strict_types=1);

// JSON javob
header('Content-Type: application/json; charset=utf-8');

// Faqat GET ruxsat
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// user_id tekshiruvi
$userId = ((isset($_GET['user_id'])) ? $_GET['user_id'] : 284914591);

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user_id']);
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
 * 1. Mijoz tilini aniqlash
 */
$stmt = $pdo->prepare("
    SELECT language 
    FROM clients 
    WHERE chat_id = :chat_id 
    LIMIT 1
");
$stmt->execute(['chat_id' => $userId]);
$client = $stmt->fetch();

$language = (int)($client['language'] ?? 1);

// Til ustuni
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
 * 2. Kategoriyalarni olish
 */
$stmt = $pdo->query("
    SELECT id, {$nameColumn} AS name, image
    FROM categories
    ORDER BY id ASC
");

$categories = $stmt->fetchAll();

/**
 * 3. Javob
 */
echo json_encode([
    'success' => true,
    'categories' => $categories
], JSON_UNESCAPED_UNICODE);
