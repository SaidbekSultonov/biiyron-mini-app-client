const userId = $('#telegram_user_id').attr('data-id');
const userLang = $('#user_lang').attr('data-lang');
const productId = $('#product_id').attr('data-id');
const warehouseId = $('#warehouse_id').attr('data-id');
// msg - PHP dan inline script orqali keladi (bot/language/*.php)

let product = null;
let currentQuantity = 1;
let maxStock = 0;
let minLimit = 0;
let maxLimit = 0;
let cartItem = null;

async function loadProduct() {
    try {
        const response = await fetch(`${apiUrl}/products/${productId}?lang=${userLang}&user_id=${userId}`);
        const data = await response.json();

        if (data.success) {
            product = data.product;
            maxStock = product.stock || 0;
            minLimit = product.min_limit || 0;
            maxLimit = product.max_limit || 0;
            await loadCart();
            renderProduct();
        } else {
            showError(msg.product_not_found);
        }

    } catch (error) {
        console.error('Xato:', error);
        showError(msg.connection_error);
    }
}

async function loadCart() {
    try {
        const response = await fetch(`${apiUrl}/cart?client_id=${userId}&lang=${userLang}`);
        const data = await response.json();

        if (data.success) {
            cartItem = data.items.find(item => item.product_id == productId);

            if (cartItem) {
                currentQuantity = parseFloat(cartItem.quantity);
            } else {
                currentQuantity = 1;
            }
        }
    } catch (error) {
        console.error('Cart yuklanmadi:', error);
    }
}

function renderProduct() {
    $('#productImage').attr('src', apiUrl.replace('/api', '') + '/' + product.image);
    $('#productName').text(product.name);
    $('#productPrice').text(Number(product.price).toLocaleString() + ' ' + msg.som);

    // Ombordagi qoldiqni ko'rsatish
    let stockText = '';
    if (product.type == 1) {
        stockText = product.stock + ' ' + msg.unit_ta;
    } else {
        const gr = product.stock;
        const kg = (gr / 1000).toFixed(1);
        stockText = kg + ' ' + msg.unit_kg + ' (' + gr + ' ' + msg.unit_gr + ')';
    }
    $('#productStock').text(stockText);

    // Min/max limit ko'rsatish
    if (minLimit > 0 || maxLimit > 0) {
        const unit = product.type == 1 ? msg.unit_ta : msg.unit_kg;
        let limitsHtml = '';
        if (minLimit > 0) {
            limitsHtml += `<span class="limit-badge"><span class="limit-badge-label">${msg.min_order_label}</span>&nbsp;${minLimit} ${unit}</span>`;
        }
        if (maxLimit > 0) {
            limitsHtml += `<span class="limit-badge max"><span class="limit-badge-label">${msg.max_order_label}</span>&nbsp;${maxLimit} ${unit}</span>`;
        }
        $('#productLimits').html(limitsHtml).removeClass('hidden');
    }

    $('#productDescription').text(product.description || msg.no_description);

    updateUI();

    $('#skeleton-product').addClass('hidden');
    $('#real-product').removeClass('hidden');
    $('#bottomCart').show();
}

function showError(message) {
    $('#skeleton-product').addClass('hidden');
    $('#real-product').html(`<p style="text-align:center; padding:40px; color:#666;">${message}</p>`).removeClass('hidden');
}

async function increaseQuantity() {
    const step = product.is_blocked ? product.min_limit : 1;

    const requiredStock = product.type == 2
        ? (currentQuantity + step) * product.product_weight
        : currentQuantity + step;

    if (requiredStock <= maxStock) {
        currentQuantity += step;
        updateUI();
        await saveToCart();
    } else {
        const maxAvailable = product.type == 2
            ? Math.floor(maxStock / product.product_weight)
            : maxStock;
        showToast(msg.only_available.replace('%d', maxAvailable), 'error');
    }
}

async function decreaseQuantity() {
    const step = product.is_blocked ? product.min_limit : 1;

    if (currentQuantity > step) {
        if (!product.is_blocked && minLimit > 0 && currentQuantity <= minLimit) {
            const unit = product.type == 1 ? msg.unit_ta : msg.unit_kg;
            showToast(msg.min_order_label + ' ' + minLimit + ' ' + unit, 'error');
            return;
        }
        currentQuantity -= step;
        updateUI();
        await saveToCart();
    } else if (currentQuantity <= step && cartItem) {
        await removeFromCart();
    }
}

function updateUI() {
    const inCart = cartItem && currentQuantity > 0;

    if (inCart) {
        $('#bottomCart').html(`
            <div class="cart-info">
                <div class="cart-total-label">${msg.total_label}</div>
                <div id="cartTotal" class="cart-total-price">${(currentQuantity * product.price).toLocaleString()} ${msg.som}</div>
            </div>
            <div class="cart-controls-bottom">
                <button class="cart-btn-bottom" onclick="decreaseQuantity()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
                <span id="cartQuantity" class="cart-quantity-bottom">${currentQuantity}</span>
                <button class="cart-btn-bottom" onclick="increaseQuantity()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            </div>
        `);
    } else {
        $('#bottomCart').html(`
            <div class="cart-info">
                <div class="cart-total-label">${msg.price_label}</div>
                <div id="cartTotal" class="cart-total-price">${Number(product.price).toLocaleString()} ${msg.som}</div>
            </div>
            <button class="add-btn-detail" onclick="addFirstTime()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
        `);
    }
}

async function addFirstTime() {
    currentQuantity = product.is_blocked ? product.min_limit : (minLimit > 0 ? minLimit : 1);
    await saveToCart();
}

async function saveToCart() {
    try {
        if (cartItem) {
            const response = await fetch(`${apiUrl}/cart/update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cart_id: cartItem.id,
                    quantity: currentQuantity,
                    lang: userLang
                })
            });

            const data = await response.json();
            if (data.success) {
                await loadCart();
                updateCartBadge();
                updateUI();
            } else {
                showToast(data.message || msg.error, 'error');
                currentQuantity = parseFloat(cartItem.quantity);
                updateUI();
            }
        } else {
            const response = await fetch(`${apiUrl}/cart/add`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    chat_id: userId,
                    product_id: productId,
                    quantity: currentQuantity,
                    lang: userLang
                })
            });

            const data = await response.json();
            if (data.success) {
                await loadCart();
                updateCartBadge();
                updateUI();
                showToast(msg.added_to_cart, 'success');
            } else {
                showToast(data.message || msg.error, 'error');
                currentQuantity = 1;
                updateUI();
            }
        }
    } catch (error) {
        console.error('Xato:', error);
        showToast(msg.error, 'error');
    }
}

async function removeFromCart() {
    try {
        const response = await fetch(`${apiUrl}/cart/remove`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart_id: cartItem.id, lang: userLang })
        });

        const data = await response.json();
        if (data.success) {
            cartItem = null;
            currentQuantity = 1;
            updateCartBadge();
            updateUI();
            showToast(msg.removed_from_cart, 'success');
        }
    } catch (error) {
        console.error('Xato:', error);
    }
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
    }, 2000);
}

loadProduct();
updateCartBadge();
