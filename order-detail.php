<?php
    date_default_timezone_set("Asia/Tashkent");
    require_once 'bot/db.php';

    $user_id = $_GET['user_id'] ?? 284914591;
    $stmt = $conn->prepare("SELECT language FROM clients WHERE chat_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    $lang_code = match((int)($client['language'] ?? 1)) {
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
    <link rel="stylesheet" href="css/order-detail.css?v=<?= $version ?>">
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

    <div class="header">
        <span class="back-btn" onclick="window.history.back()">←</span>
        <h1 class="header-title" id="pageTitle"><?= $lang['header_orders'] ?></h1>
    </div>

    <div class="content" id="orderContent">
        <!-- JS renders here -->
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

        <a href="history.php?user_id=<?= $user_id ?>" class="nav-item active">
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
        const statusTextMap = {
            new:       msg.status_new,
            confirmed: msg.status_confirmed,
            sent:      msg.status_sent,
            delivered: msg.status_delivered,
            archived:  msg.status_archived,
            cancelled: msg.status_cancelled,
        };

        function render(order) {
            document.getElementById('pageTitle').textContent = '#' + order.order_number;

            const itemsTotal = order.total - (order.delivery_fee || 0);
            const statusText = statusTextMap[order.status] || order.status;

            const itemsHtml = order.items.map(item => `
                <div class="item-row">
                    <div class="item-left">
                        <div class="item-badge">${item.qty}×</div>
                        <div class="item-name">${item.name}</div>
                    </div>
                    <div class="item-price">${Number(item.price * item.qty).toLocaleString()} ${msg.som}</div>
                </div>
            `).join('');

            let deliveryBanner = '';
            if (order.delivery_date && order.delivery_time) {
                deliveryBanner = `<div class="delivery-banner">📅 ${msg.delivery_info_label} ${order.delivery_date} ${order.delivery_time}</div>`;
            }

            const html = `
                <div class="section-card">
                    <div class="meta-row">
                        <span class="meta-key">${msg.orders_all !== undefined ? '№' : '№'}</span>
                        <span class="meta-val">#${order.order_number}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-key">📅</span>
                        <span class="meta-val">${order.date}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-key"></span>
                        <span class="status-badge status-${order.status}">${statusText}</span>
                    </div>
                    ${order.delivery_date && order.delivery_time ? `
                    <div class="meta-row">
                        <span class="meta-key">${msg.delivery_info_label}</span>
                        <span class="meta-val">${order.delivery_date} ${order.delivery_time}</span>
                    </div>` : ''}
                </div>

                <div class="section-card">
                    <div class="section-label">${msg.products_label}</div>
                    ${itemsHtml}
                </div>

                <div class="section-card">
                    <div class="summary-row">
                        <span>${msg.products_label}</span>
                        <span>${Number(itemsTotal).toLocaleString()} ${msg.som}</span>
                    </div>
                    <div class="summary-row">
                        <span>${msg.delivery_fee_row}</span>
                        <span>${Number(order.delivery_fee || 0).toLocaleString()} ${msg.som}</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>${msg.total_amount_label}</span>
                        <span>${Number(order.total).toLocaleString()} ${msg.som}</span>
                    </div>
                </div>
            `;

            document.getElementById('orderContent').innerHTML = html;
        }

        const raw = localStorage.getItem('order_detail');
        if (raw) {
            try {
                render(JSON.parse(raw));
            } catch(e) {
                window.history.back();
            }
        } else {
            window.history.back();
        }
    </script>
</body>
</html>
