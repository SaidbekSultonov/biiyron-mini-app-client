const userId = $('#telegram_user_id').attr('data-id');
const userLang = $('#user_lang').attr('data-lang');
// const apiUrl = 'http://localhost:8888/public/api';

let cartItems = [];
let selectedShopId = null;

async function loadCart() {
    try {
        const response = await fetch(`${apiUrl}/cart?client_id=${userId}&lang=${userLang}`);
        const data = await response.json();

        if (data.success) {
            cartItems = data.items;
            renderCart(data);
        }

    } catch (error) {
        console.error('Xato:', error);
        $('#skeleton-cart').hide();
        $('#emptyCart').show();
    }
}

function renderCart(cartData) {
    $('#skeleton-cart').hide();

    if (cartItems.length === 0) {
        $('#emptyCart').show();
        $('#cartItems').hide();
        $('#shopsSection').hide();
        $('#cartSummary').hide();
        return;
    }

    const container = $('#cartItems');
    container.empty();

    cartItems.forEach(item => {
        const itemTotal = item.quantity * item.price;

        let quantityInfo = '';
        let weightInfo = '';
        let totalInfo = '';

        if (item.type == 1) {
            quantityInfo = `<span class="info-label">${msg.qty_label}</span> <span class="info-value">${item.quantity} ${msg.unit_ta}</span>`;
            weightInfo = `<span class="info-label">${msg.weight_label}</span> <span class="info-value">${item.product_weight || 0} ${msg.unit_gr}</span>`;
            const itemTotalWeight = item.quantity * (item.product_weight || 0);
            totalInfo = `<span class="total-label">${msg.total_label}</span> <span class="total-value">${Number(itemTotal).toLocaleString()} ${msg.som} | ${itemTotalWeight} ${msg.unit_gr}</span>`;
        } else {
            const itemTotalWeight = item.quantity * 1000;
            quantityInfo = `<span class="info-label">${msg.weight_label}</span> <span class="info-value">${item.quantity} ${msg.unit_kg} (${itemTotalWeight} ${msg.unit_gr})</span>`;
            totalInfo = `<span class="total-label">${msg.total_label}</span> <span class="total-value">${Number(itemTotal).toLocaleString()} ${msg.som}</span>`;
        }

        container.append(`
            <div class="cart-card">
                <div class="card-header">
                    <img src="${apiUrl.replace('/api', '')}/${item.image}" alt="${item.name}" class="card-image">
                    <div class="card-title-block">
                        <div class="card-title">${item.name}</div>
                        <div class="card-price">${Number(item.price).toLocaleString()} ${msg.som}</div>
                    </div>
                </div>

                <div class="card-info">
                    <div class="info-row">
                        <span class="info-label">${msg.price_label}</span>
                        <span class="info-value">${Number(item.price).toLocaleString()} ${msg.som}</span>
                    </div>
                    <div class="info-row">
                        ${quantityInfo}
                    </div>
                    ${item.type == 1 ? `<div class="info-row">${weightInfo}</div>` : ''}
                    <div class="info-row info-total">
                        ${totalInfo}
                    </div>
                </div>

                <div class="card-controls">
                    <div class="item-controls">
                        <button class="btn-control" onclick="decreaseQty(${item.id})">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </button>
                        <span class="item-quantity">${item.quantity}</span>
                        <button class="btn-control" onclick="increaseQty(${item.id})">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </button>
                    </div>
                    <button class="btn-delete-cart" onclick="removeItem(${item.id})">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `);
    });

    // Shops render qilish
    if (cartData.shops && cartData.shops.length > 0) {
        renderShops(cartData.shops);
    }

    const total = cartData.total;
    const totalWeight = cartData.total_weight;
    const totalWeightKg = (totalWeight / 1000).toFixed(2);

    $('#cartSummary').html(`
        <div class="summary-row">
            <span>${msg.products_total}</span>
            <span>${Number(total).toLocaleString()} ${msg.som}</span>
        </div>
        <div class="summary-row">
            <span>${msg.products_weight_label}</span>
            <span>${totalWeightKg} ${msg.unit_kg} / ${totalWeight} ${msg.unit_gr}</span>
        </div>
        <div class="summary-row summary-total">
            <span>${msg.grand_total_label}</span>
            <span>${Number(total).toLocaleString()} ${msg.som}</span>
        </div>
        <button class="btn-checkout" onclick="checkout()">${msg.btn_checkout}</button>
    `);

    $('#emptyCart').hide();
    $('#cartItems').show();
    $('#cartSummary').show();

    updateCartBadge();
}

function renderShops(shops) {
    if (shops.length === 0) return;

    // Birinchi do'konni avtomatik tanlash (DESC order - oxirgi qo'shilgan birinchi)
    if (!selectedShopId) {
        selectedShopId = shops[0].id;
    }

    const shopsContainer = $('#shopsSection');
    shopsContainer.empty();

    shopsContainer.append(`<div class="shops-title">${msg.select_shop}</div>`);

    shops.forEach(shop => {
        const isSelected = shop.id === selectedShopId;

        shopsContainer.append(`
            <div class="shop-card ${isSelected ? 'selected' : ''}" onclick="selectShop(${shop.id})">
                <div class="shop-radio">
                    <div class="radio-circle ${isSelected ? 'checked' : ''}"></div>
                </div>
                <div class="shop-details">
                    <div class="shop-name">${shop.name}</div>
                    <div class="shop-address">${shop.address || ''}</div>
                    ${shop.desc ? `<div class="shop-desc">${shop.desc}</div>` : ''}
                </div>
            </div>
        `);
    });

    shopsContainer.show();
}

function selectShop(shopId) {
    selectedShopId = shopId;

    $('.shop-card').removeClass('selected');
    $('.radio-circle').removeClass('checked');

    $(`[onclick="selectShop(${shopId})"]`).addClass('selected').find('.radio-circle').addClass('checked');
}

async function increaseQty(cartId) {
    const item = cartItems.find(i => i.id === cartId);
    if (!item) return;

    try {
        const response = await fetch(`${apiUrl}/cart/update`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cart_id: cartId,
                quantity: parseFloat(item.quantity) + 1,
                lang: userLang
            })
        });

        const data = await response.json();

        if (data.success) {
            await loadCart();
        } else {
            showToast(data.message || msg.error, 'error');
        }

    } catch (error) {
        console.error('Xato:', error);
        showToast(msg.error, 'error');
    }
}

async function decreaseQty(cartId) {
    const item = cartItems.find(i => i.id === cartId);
    if (!item) return;

    if (parseFloat(item.quantity) > 1) {
        try {
            const response = await fetch(`${apiUrl}/cart/update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cart_id: cartId,
                    quantity: parseFloat(item.quantity) - 1,
                    lang: userLang
                })
            });

            const data = await response.json();

            if (data.success) {
                await loadCart();
            } else {
                showToast(data.message || msg.error, 'error');
            }

        } catch (error) {
            console.error('Xato:', error);
            showToast(msg.error, 'error');
        }
    } else {
        removeItem(cartId);
    }
}

async function removeItem(cartId) {
    if (!confirm(msg.confirm_remove)) return;

    try {
        const response = await fetch(`${apiUrl}/cart/remove`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart_id: cartId, lang: userLang })
        });

        const data = await response.json();
        if (data.success) {
            await loadCart();
        }

    } catch (error) {
        console.error('Xato:', error);
    }
}

async function checkout() {
    if (!selectedShopId) {
        showToast(msg.please_select_shop, 'error');
        return;
    }

    $('.btn-checkout').prop('disabled', true).css('opacity', '0.5');

    showLoadingModal();

    await new Promise(resolve => setTimeout(resolve, 3000));

    try {
        const response = await fetch(`${apiUrl}/order/create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_id: userId,
                shop_id: selectedShopId,
                lang: userLang
            })
        });

        const data = await response.json();

        if (data.success) {
            hideModal();
            showToast(msg.order_accepted, 'success');

            setTimeout(() => {
                window.location.href = 'history.php';
            }, 1500);

        } else {
            if (data.below_min_limit_products) {
                showBelowMinLimitModal(data.below_min_limit_products);
            } else if (data.insufficient_products) {
                showInsufficientModal(data.insufficient_products);
            } else {
                hideModal();
                showToast(data.message || msg.error, 'error');
            }
            $('.btn-checkout').prop('disabled', false).css('opacity', '1');
        }

    } catch (error) {
        console.error('Xato:', error);
        hideModal();
        showToast(msg.error, 'error');
        $('.btn-checkout').prop('disabled', false).css('opacity', '1');
    }
}

function showLoadingModal() {
    const modal = `
        <div class="modal-overlay" id="loadingModal">
            <div class="modal-content">
                <div class="loader"></div>
                <div class="modal-text">${msg.checking_cart}</div>
            </div>
        </div>
    `;
    $('body').append(modal);
    $('body').css('overflow', 'hidden');
}

function showInsufficientModal(insufficientProducts) {
    $('#loadingModal').remove();

    let productsList = '';
    insufficientProducts.forEach(product => {
        productsList += `
            <div class="insufficient-item">
                <div class="insufficient-name">${product.name}</div>
                <div class="insufficient-details">
                    ${msg.required_label} ${product.required} ${product.unit} |
                    ${msg.warehouse_label} ${product.available} ${product.unit}
                </div>
            </div>
        `;
    });

    const modal = `
        <div class="modal-overlay" id="insufficientModal">
            <div class="modal-content modal-error">
                <div class="modal-icon">⚠️</div>
                <div class="modal-title">${msg.insufficient_title}</div>
                <div class="insufficient-list">
                    ${productsList}
                </div>
                <button class="btn-modal-close" onclick="hideModal()">${msg.modify_cart}</button>
            </div>
        </div>
    `;
    $('body').append(modal);
}

function showBelowMinLimitModal(products) {
    $('#loadingModal').remove();

    let productsList = '';
    products.forEach(p => {
        productsList += `
            <div class="insufficient-item">
                <div class="insufficient-name">${p.name}</div>
                <div class="insufficient-details">
                    ${msg.your_qty_label} ${p.quantity} ${p.unit} |
                    ${msg.min_label} ${p.min_limit} ${p.unit}
                </div>
            </div>
        `;
    });

    const modal = `
        <div class="modal-overlay" id="belowMinModal">
            <div class="modal-content modal-error">
                <div class="modal-icon">⚠️</div>
                <div class="modal-title">${msg.below_min_limit_title}</div>
                <div class="insufficient-list">
                    ${productsList}
                </div>
                <button class="btn-modal-close" onclick="hideModal()">${msg.modify_cart}</button>
            </div>
        </div>
    `;
    $('body').append(modal);
}

function hideModal() {
    $('#loadingModal').remove();
    $('#insufficientModal').remove();
    $('#belowMinModal').remove();
    $('body').css('overflow', 'auto');
}

function showToast(message, type = 'success') {
    const toast = $(`
        <div class="toast toast-${type}">
            ${message}
        </div>
    `);

    $('body').append(toast);

    setTimeout(() => {
        toast.addClass('show');
    }, 100);

    setTimeout(() => {
        toast.removeClass('show');
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

loadCart();
