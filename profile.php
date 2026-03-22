<?php
    date_default_timezone_set("Asia/Tashkent");
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once 'bot/db.php';

    $user_id = $_GET['user_id'] ?? 284914591;
    $stmt = $conn->prepare("
        SELECT
            c.*,
            s.id AS shop_id,
            s.name AS shop_name,
            s.employees_id,
            s.address,
            (SELECT COUNT(*) FROM shops WHERE client_id = c.id AND status = 1 AND deleted_at IS NULL) AS shops_count
        FROM clients c
        LEFT JOIN shops s ON s.client_id = c.id AND s.status = 1
            AND (c.temp_warehouse_id IS NULL OR s.warehouse_id = c.temp_warehouse_id)
        WHERE c.chat_id = ?
        ORDER BY s.id DESC
        LIMIT 1
    ");

    $stmt->execute([$user_id]);
    $client_shop = $stmt->fetch(PDO::FETCH_ASSOC);

    $lang_code = match((int)($client_shop['language'] ?? 1)) {
        1 => 'uz',
        2 => 'kr',
        default => 'ru'
    };

    $owner_name   = $client_shop['owner_name']   ?? '';
    $phone_number = $client_shop['phone_number'] ?? '';
    $shops_count  = (int)($client_shop['shops_count'] ?? 0);

    include "language/{$lang_code}.php";
    $version = time();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['page_title_profile'] ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>const apiUrl = '<?= APP_URL ?>/public/api';</script>
    <script>const msg = <?= json_encode($lang, JSON_UNESCAPED_UNICODE) ?>;</script>
    <script>
        let tg = window.Telegram.WebApp;
        tg.expand();
        tg.BackButton.show();
        tg.BackButton.onClick(() => window.history.back());
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Open Sans", sans-serif;
            background: #f5f5f5;
            color: #000;
            padding-bottom: 80px;
        }

        .header {
            background: linear-gradient(135deg, #007c00, #00a000);
            padding: 30px 20px;
            color: white;
            text-align: center;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .profile-name {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .profile-phone {
            font-size: 14px;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 15px;
            margin-top: -30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #007c00;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }

        .menu-section {
            background: white;
            margin: 15px;
            border-radius: 12px;
            overflow: hidden;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .menu-item:active {
            background: #f5f5f5;
        }

        .menu-icon {
            width: 40px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
        }

        .menu-content {
            flex: 1;
        }

        .menu-title {
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .menu-subtitle {
            font-size: 13px;
            color: #666;
        }

        .menu-arrow {
            color: #999;
            font-size: 18px;
        }

        /* Language modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: flex-end;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-sheet {
            background: white;
            border-radius: 20px 20px 0 0;
            width: 100%;
            padding: 20px 20px 40px;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .lang-option {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            border: 2px solid #f0f0f0;
            transition: border-color 0.2s, background 0.2s;
        }

        .lang-option.selected {
            border-color: #007c00;
            background: #f0fff0;
        }

        .lang-option-text {
            font-size: 15px;
            font-weight: 500;
            flex: 1;
        }

        .lang-check {
            color: #007c00;
            font-size: 20px;
            display: none;
        }

        .lang-option.selected .lang-check {
            display: block;
        }

        .bottom-nav {
            position: fixed;
            bottom: 5px;
            left: 5px;
            right: 5px;
            background: white;
            box-shadow: 0 0 10px grey;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 5px;
            border-radius: 10px;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            flex: 1;
        }

        .nav-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: #000;
            position: relative;
        }

        .nav-item.active .nav-icon {
            background: #007c00;
            color: white;
        }

        .nav-item.cart {
            margin-top: -30px;
        }

        .nav-item.cart .nav-icon {
            width: 70px;
            height: 70px;
            background: #CCC;
            color: #000;
            border-radius: 50%;
        }

        .nav-icon svg {
            width: 28px;
            height: 28px;
            stroke: currentColor;
        }

        .nav-item.cart .nav-icon svg {
            width: 35px;
            height: 35px;
        }

        .cart-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #ff4757;
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 7px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="profile-avatar">👤</div>
        <div class="profile-name" id="userName"><?= htmlspecialchars($owner_name ?: $lang['profile_default_name']) ?></div>
        <div class="profile-phone" id="userPhone"><?= htmlspecialchars($phone_number) ?></div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value" id="ordersCount">0</div>
            <div class="stat-label"><?= $lang['profile_orders_label'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="shopsCount"><?= $shops_count ?></div>
            <div class="stat-label"><?= $lang['profile_shops_label'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="balanceAmount">0</div>
            <div class="stat-label"><?= $lang['profile_balance_label'] ?></div>
        </div>
    </div>

    <div class="menu-section">
        <div class="menu-item" onclick="window.location='history.php?user_id=<?= $user_id ?>'">
            <div class="menu-icon">📦</div>
            <div class="menu-content">
                <div class="menu-title"><?= $lang['profile_my_orders'] ?></div>
                <div class="menu-subtitle"><?= $lang['profile_my_orders_sub'] ?></div>
            </div>
            <div class="menu-arrow">›</div>
        </div>

        <div class="menu-item" onclick="window.location='shops.php?user_id=<?= $user_id ?>'">
            <div class="menu-icon">🏪</div>
            <div class="menu-content">
                <div class="menu-title"><?= $lang['profile_addresses'] ?></div>
                <div class="menu-subtitle"><?= $lang['profile_addresses_sub'] ?> (<?= $shops_count ?>)</div>
            </div>
            <div class="menu-arrow">›</div>
        </div>

    </div>

    <div class="menu-section">
        <div class="menu-item" onclick="showLangModal()">
            <div class="menu-icon">🌐</div>
            <div class="menu-content">
                <div class="menu-title"><?= $lang['profile_lang'] ?></div>
                <div class="menu-subtitle"><?= $lang['profile_lang_sub'] ?></div>
            </div>
            <div class="menu-arrow">›</div>
        </div>

        <div class="menu-item">
            <div class="menu-icon">🔔</div>
            <div class="menu-content">
                <div class="menu-title"><?= $lang['profile_notifications'] ?></div>
                <div class="menu-subtitle"><?= $lang['profile_notifications_sub'] ?></div>
            </div>
            <div class="menu-arrow">›</div>
        </div>

        <div class="menu-item">
            <div class="menu-icon">❓</div>
            <div class="menu-content">
                <div class="menu-title"><?= $lang['profile_help'] ?></div>
                <div class="menu-subtitle"><?= $lang['profile_help_sub'] ?></div>
            </div>
            <div class="menu-arrow">›</div>
        </div>

    </div>

    <!-- Language modal -->
    <div class="modal-overlay" id="langModal" onclick="closeLangModal(event)">
        <div class="modal-sheet">
            <div class="modal-title"><?= $lang['lang_modal_title'] ?></div>
            <div class="lang-option <?= $lang_code === 'uz' ? 'selected' : '' ?>" onclick="setLanguage('uz')">
                <span class="lang-option-text">O'zbek</span>
                <span class="lang-check">✓</span>
            </div>
            <div class="lang-option <?= $lang_code === 'kr' ? 'selected' : '' ?>" onclick="setLanguage('kr')">
                <span class="lang-option-text">Ўзбек</span>
                <span class="lang-check">✓</span>
            </div>
            <div class="lang-option <?= $lang_code === 'ru' ? 'selected' : '' ?>" onclick="setLanguage('ru')">
                <span class="lang-option-text">Русский</span>
                <span class="lang-check">✓</span>
            </div>
        </div>
    </div>

    <div id="telegram_user_id" data-id="<?= $user_id ?>"></div>
    <div id="user_lang" data-lang="<?= $lang_code ?>"></div>

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

        <div class="nav-item active">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M4 22c0-4.4 3.6-8 8-8s8 3.6 8 8"></path>
                </svg>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="js/cart-badge.js?v=<?= $version ?>"></script>
    <script>
        updateCartBadge();
    </script>
    <script>
        const userId = $('#telegram_user_id').attr('data-id');

        async function loadProfile() {
            try {
                const response = await fetch(`${apiUrl}/orders?client_id=${userId}`);
                const data = await response.json();
                if (data && data.length !== undefined) {
                    $('#ordersCount').text(data.length);
                }
            } catch (error) {
                console.error('Profilni yuklashda xato:', error);
            }
        }

        function showLangModal() {
            document.getElementById('langModal').classList.add('active');
        }

        function closeLangModal(event) {
            if (event.target === document.getElementById('langModal')) {
                document.getElementById('langModal').classList.remove('active');
            }
        }

        async function setLanguage(lang) {
            await fetch('api/set-language.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, lang: lang })
            });
            window.location.reload();
        }

        loadProfile();
    </script>
</body>
</html>
