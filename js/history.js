const userId = $('#telegram_user_id').attr('data-id');
const userLang = $('#user_lang').attr('data-lang');
// msg - PHP dan inline script orqali keladi (bot/language/*.php)

let allOrders = [];
let currentFilter = 'all';

function getStatusText(status) {
    const map = {
        new:       msg.status_new,
        confirmed: msg.status_confirmed,
        sent:      msg.status_sent,
        delivered: msg.status_delivered,
        archived:  msg.status_archived,
        cancelled: msg.status_cancelled,
    };
    return map[status] || status;
}

async function loadOrders() {
    try {
        const response = await fetch(`${apiUrl}/orders?chat_id=${userId}&lang=${userLang}`);
        const data = await response.json();

        allOrders = data.success ? data.orders : [];
        renderOrders();

        $('#skeletonOrders').addClass('hidden');
        $('#realOrders').removeClass('hidden');

    } catch (error) {
        console.error('Buyurtmalarni yuklashda xato:', error);
        allOrders = [];
        renderOrders();
        $('#skeletonOrders').addClass('hidden');
        $('#realOrders').removeClass('hidden');
    }
}

function renderOrders() {
    const container = $('#realOrders');
    container.empty();

    const filtered = allOrders.filter(order => {
        if (currentFilter === 'all')       return true;
        if (currentFilter === 'active')    return ['new', 'confirmed', 'sent'].includes(order.status);
        if (currentFilter === 'completed') return ['delivered', 'archived', 'cancelled'].includes(order.status);
        return true;
    });

    if (filtered.length === 0) {
        container.html(`
            <div class="empty-orders">
                <div class="empty-icon">📦</div>
                <div class="empty-text">${msg.no_orders}</div>
            </div>
        `);
        return;
    }

    filtered.forEach(order => {
        container.append(`
            <div class="order-card" onclick="openOrder(${order.id})">
                <div class="order-row">
                    <div class="order-number">#${order.order_number}</div>
                    <div class="order-status status-${order.status}">${getStatusText(order.status)}</div>
                </div>
                <div class="order-date-row">📅 ${order.date}</div>
            </div>
        `);
    });
}

function openOrder(orderId) {
    const order = allOrders.find(o => o.id === orderId);
    if (!order) return;
    localStorage.setItem('order_detail', JSON.stringify(order));
    window.location.href = `order-detail.php?user_id=${userId}`;
}

$('.tab').click(function () {
    $('.tab').removeClass('active');
    $(this).addClass('active');
    currentFilter = $(this).data('status');
    renderOrders();
});

loadOrders();
updateCartBadge();
