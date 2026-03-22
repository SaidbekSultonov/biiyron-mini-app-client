<?php 
    date_default_timezone_set("Asia/Tashkent"); 
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', 1);

    require_once 'bot/db.php'; 

    $user_id = $_GET['user_id'] ?? 284914591;
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            s.id AS shop_id,
            s.name AS shop_name,
            s.employees_id,
            s.address
        FROM clients c
        INNER JOIN shops s ON s.client_id = c.id
        WHERE c.chat_id = ? AND s.status = ?
        LIMIT 1
    ");

    $stmt->execute([$user_id, 1]);
    $client_shop = $stmt->fetch(PDO::FETCH_ASSOC);

    $lang_code = match((int)($client_shop['language'] ?? 1)) {
        1 => 'uz',
        2 => 'kr',
        default => 'ru'
    };

    include "language/{$lang_code}.php";
    $version = time();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['page_title_history'] ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/history.css?v=<?= $version ?>">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>const apiUrl = '<?= APP_URL ?>/public/api';</script>
    <script>const msg = <?= json_encode($lang, JSON_UNESCAPED_UNICODE) ?>;</script>
    <script>
        let tg = window.Telegram.WebApp;
        tg.expand();
        
        if (tg.version >= '6.1') {
            tg.BackButton.show();
            tg.BackButton.onClick(() => window.history.back());
        }
    </script>
</head>
<body>
    
    <div class="header">
        <span class="back-btn" onclick="window.history.back()">←</span>
        <h1 class="header-title"><?= $lang['header_orders'] ?></h1>
    </div>

    <div class="tabs">
        <div class="tab active" data-status="all"><?= $lang['orders_all'] ?></div>
        <div class="tab" data-status="active"><?= $lang['orders_active'] ?></div>
        <div class="tab" data-status="completed"><?= $lang['orders_completed'] ?></div>
    </div>

    <div class="orders-list" id="skeletonOrders">
        <div class="skeleton-order">
            <div class="skeleton skeleton-line" style="width: 60%;"></div>
            <div class="skeleton skeleton-line" style="width: 40%;"></div>
            <div class="skeleton skeleton-line" style="width: 80%;"></div>
            <div class="skeleton skeleton-line" style="width: 50%;"></div>
        </div>
        <div class="skeleton-order">
            <div class="skeleton skeleton-line" style="width: 60%;"></div>
            <div class="skeleton skeleton-line" style="width: 40%;"></div>
            <div class="skeleton skeleton-line" style="width: 80%;"></div>
            <div class="skeleton skeleton-line" style="width: 50%;"></div>
        </div>
    </div>

    <div class="orders-list hidden" id="realOrders">
    </div>

    <div id="telegram_user_id" data-id="<?= $user_id ?>"></div>
    <div id="user_lang" data-lang="<?= $lang_code ?>"></div>

    <div class="bottom-nav">
        <a href="index.php" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
        </a>
        
        <a href="categories.php" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                </svg>
            </div>
        </a>
        
        <a href="cart.php" class="nav-item cart">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="cart-badge">0</span>
            </div>
        </a>
        
        <a href="history.php" class="nav-item active">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15"/>
                    <path d="M12 12H15"/>
                    <path d="M12 16H15"/>
                    <path d="M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5V7H9V5Z"/>
                    <circle cx="9" cy="12" r="1"/>
                    <circle cx="9" cy="16" r="1"/>
                </svg>
            </div>
        </a>
        
        <a href="profile.php" class="nav-item">
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
    <script src="js/history.js?v=<?= $version ?>"></script>
</body>
</html>
