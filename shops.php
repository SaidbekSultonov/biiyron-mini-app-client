<?php
    date_default_timezone_set("Asia/Tashkent");
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', 1);

    require_once 'bot/db.php';

    $user_id = $_GET['user_id'] ?? 284914591;

    // Get client info to determine current lang and active warehouse
    $stmt = $conn->prepare("SELECT id, language, temp_warehouse_id FROM clients WHERE chat_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        header('Location: index.php');
        exit;
    }

    $lang_code = match((int)($client['language'] ?? 1)) {
        1 => 'uz',
        2 => 'ru',
        3 => 'kr',
        default => 'uz'
    };

    $district_col = match($lang_code) {
        'kr'    => 'd.name_kr',
        'ru'    => 'd.name_ru',
        default => 'd.name_uz',
    };

    include "language/{$lang_code}.php";

    // Fetch all active shops with district name, ordered ASC by created_at
    $shops_stmt = $conn->prepare("
        SELECT
            s.id,
            s.name,
            s.address,
            s.warehouse_id,
            s.created_at,
            {$district_col} AS district_name
        FROM shops s
        LEFT JOIN warehouses w ON w.id = s.warehouse_id AND w.deleted_at IS NULL
        LEFT JOIN districts d ON d.id = w.district_id
        WHERE s.client_id = ? AND s.status = 1 AND s.deleted_at IS NULL
        ORDER BY s.created_at ASC
    ");
    $shops_stmt->execute([$client['id']]);
    $shops = $shops_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Determine active shop id
    $active_warehouse_id = $client['temp_warehouse_id'];
    $active_shop_id = null;
    if ($active_warehouse_id) {
        foreach ($shops as $shop) {
            if ((int)$shop['warehouse_id'] === (int)$active_warehouse_id) {
                $active_shop_id = $shop['id'];
                break;
            }
        }
    }
    // Fallback: last shop by id
    if (!$active_shop_id && !empty($shops)) {
        $sorted = $shops;
        usort($sorted, fn($a, $b) => $b['id'] - $a['id']);
        $active_shop_id = $sorted[0]['id'];
    }

    $version = time();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['page_title_shops'] ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/shops.css?v=<?= $version ?>">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>const msg = <?= json_encode($lang, JSON_UNESCAPED_UNICODE) ?>;</script>
    <script>
        let tg = window.Telegram.WebApp;
        tg.expand();
        tg.BackButton.show();
        tg.BackButton.onClick(() => window.history.back());
    </script>
</head>
<body>

    <div class="header"><?= $lang['header_shops'] ?></div>

    <div class="shops-list">
        <?php foreach ($shops as $shop): ?>
        <div class="shop-item <?= (int)$shop['id'] === (int)$active_shop_id ? 'selected' : '' ?>"
             onclick="onShopClick(<?= $shop['id'] ?>, <?= (int)$shop['id'] === (int)$active_shop_id ? 'true' : 'false' ?>)">
            <input
                type="radio"
                class="shop-radio"
                name="shop"
                value="<?= $shop['id'] ?>"
                <?= (int)$shop['id'] === (int)$active_shop_id ? 'checked' : '' ?>
                onclick="event.stopPropagation(); onShopClick(<?= $shop['id'] ?>, <?= (int)$shop['id'] === (int)$active_shop_id ? 'true' : 'false' ?>)"
            >
            <div class="shop-info">
                <div class="shop-name"><?= htmlspecialchars($shop['name']) ?></div>
                <?php if ($shop['address']): ?>
                <div class="shop-address"><?= htmlspecialchars($shop['address']) ?></div>
                <?php endif; ?>
                <?php if ($shop['district_name']): ?>
                <div class="shop-district"><?= htmlspecialchars($shop['district_name']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Confirmation modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-sheet">
            <div class="modal-title"><?= $lang['confirm_shop_switch'] ?></div>
            <div class="modal-buttons">
                <button class="btn btn-no" onclick="cancelSwitch()"><?= $lang['btn_no'] ?></button>
                <button class="btn btn-yes" onclick="confirmSwitch()"><?= $lang['btn_yes'] ?></button>
            </div>
        </div>
    </div>

    <div id="telegram_user_id" data-id="<?= $user_id ?>"></div>

    <div class="bottom-nav">
        <a href="index.php?user_id=<?= $user_id ?>" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
        </a>

        <a href="categories.php?user_id=<?= $user_id ?>" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                </svg>
            </div>
        </a>

        <a href="cart.php?user_id=<?= $user_id ?>" class="nav-item cart">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="cart-badge">0</span>
            </div>
        </a>

        <a href="history.php?user_id=<?= $user_id ?>" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 12H15M12 16H15M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5V7H9V5Z" stroke="currentColor" stroke-width="2"/>
                    <circle cx="9" cy="12" r="1" fill="currentColor"/>
                    <circle cx="9" cy="16" r="1" fill="currentColor"/>
                </svg>
            </div>
        </a>

        <a href="profile.php?user_id=<?= $user_id ?>" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M4 22c0-4.4 3.6-8 8-8s8 3.6 8 8"></path>
                </svg>
            </div>
        </a>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="js/cart-badge.js?v=<?= $version ?>"></script>
    <script>
        updateCartBadge();
    </script>
    <script>
        const userId = document.getElementById('telegram_user_id').dataset.id;
        let pendingShopId = null;

        function onShopClick(shopId, isAlreadyActive) {
            if (isAlreadyActive) return;
            pendingShopId = shopId;
            document.getElementById('confirmModal').classList.add('active');
        }

        function cancelSwitch() {
            pendingShopId = null;
            document.getElementById('confirmModal').classList.remove('active');
        }

        async function confirmSwitch() {
            if (!pendingShopId) return;
            document.getElementById('confirmModal').classList.remove('active');

            try {
                await fetch('api/switch-shop.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, shop_id: pendingShopId })
                });
            } catch (e) {
                console.error(e);
            }

            window.location.href = 'shops.php?user_id=' + userId;
        }
    </script>
</body>
</html>
