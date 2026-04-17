const userId = $('#telegram_user_id').attr('data-id');
const userLang = $('#user_lang').attr('data-lang');
const categoryId = $('#category_id').attr('data-id');
const warehouseId = $('#warehouse_id').attr('data-id');
// msg - PHP dan inline script orqali keladi (bot/language/*.php)

let allProducts = [];
let cartItems = {}; // {product_id: {cart_id, quantity}}

async function loadCart() {
    try {
        const response = await fetch(`${apiUrl}/cart?client_id=${userId}&lang=${userLang}`);
        const data = await response.json();

        if (data.success) {
            cartItems = {};
            data.items.forEach(item => {
                cartItems[item.product_id] = {
                    cart_id: item.id,
                    quantity: item.quantity
                };
            });
        }
    } catch (error) {
        console.error('Cart yuklanmadi:', error);
    }
}

async function loadProducts() {
    try {
        const response = await fetch(`${apiUrl}/products?category_id=${categoryId}&lang=${userLang}&user_id=${userId}`);
        const data = await response.json();

        if (data.success && Array.isArray(data.products)) {
            allProducts = data.products;

            if (data.category_name) {
                $('#categoryName').text(data.category_name);
            }

            renderProducts(allProducts);
        } else {
            console.error('Mahsulotlar array emas:', data);
            $('#skeleton-products').addClass('hidden');
            $('#real-products').html(`<p style="text-align:center; padding:40px; color:#666;">${msg.error}</p>`).removeClass('hidden');
        }

    } catch (error) {
        console.error('Xato:', error);
        $('#skeleton-products').addClass('hidden');
        $('#real-products').html(`<p style="text-align:center; padding:40px; color:#666;">${msg.connection_error}</p>`).removeClass('hidden');
    }
}

function renderProducts(products) {
    const container = $('#real-products');
    container.empty();

    if (!products || products.length === 0) {
        container.html(`<p style="text-align:center; padding:40px; color:#666;">${msg.no_products}</p>`);
        $('#skeleton-products').addClass('hidden');
        container.removeClass('hidden');
        return;
    }

    products.forEach((product) => {
        const price = product.price ? Number(product.price).toLocaleString() : '0';
        const inCart = cartItems[product.id];

        let cartButton = '';
        if (inCart) {
            cartButton = `
                <div class="cart-controls">
                    <button class="cart-btn cart-btn-minus" onclick="event.stopPropagation(); removeFromCart(${product.id})">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                    <span class="cart-quantity">${inCart.quantity}</span>
                    <button class="cart-btn cart-btn-plus" onclick="event.stopPropagation(); addToCart(${product.id})">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                </div>
            `;
        } else {
            cartButton = `
                <button class="add-btn" onclick="event.stopPropagation(); addToCart(${product.id})">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            `;
        }

        container.append(`
            <div class="product-card" onclick="openProduct(${product.id})">
                <img src="${apiUrl.replace('/api', '')}/${product.image}" alt="${product.name}" class="product-image">
                <div class="product-info">
                    <div class="product-name">${product.name}</div>
                    <div class="product-footer">
                        <div class="product-price">${price} ${msg.som}</div>
                        ${cartButton}
                    </div>
                </div>
            </div>
        `);
    });

    $('#skeleton-products').addClass('hidden');
    container.removeClass('hidden');
}

function openProduct(id) {
    window.location = `product-detail.php?product_id=${id}&lang=${userLang}&user_id=${userId}`;
}

async function addToCart(productId) {
    try {
        const response = await fetch(`${apiUrl}/cart/add`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_id: userId,
                product_id: productId,
                quantity: 1,
                lang: userLang
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast(msg.added_to_cart, 'success');
            await loadCart();
            renderProducts(allProducts);
            updateCartBadge();
        } else {
            showToast(data.message || msg.stock_error, 'error');
        }

    } catch (error) {
        console.error('Xato:', error);
        showToast(msg.error, 'error');
    }
}

async function removeFromCart(productId) {
    const cartItem = cartItems[productId];
    if (!cartItem) return;

    try {
        if (cartItem.quantity > 1) {
            const response = await fetch(`${apiUrl}/cart/update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cart_id: cartItem.cart_id,
                    quantity: cartItem.quantity - 1,
                    lang: userLang
                })
            });

            const data = await response.json();
            if (data.success) {
                await loadCart();
                renderProducts(allProducts);
                updateCartBadge();
            }
        } else {
            const response = await fetch(`${apiUrl}/cart/remove`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart_id: cartItem.cart_id, lang: userLang })
            });

            const data = await response.json();
            if (data.success) {
                await loadCart();
                renderProducts(allProducts);
                updateCartBadge();
            }
        }

    } catch (error) {
        console.error('Xato:', error);
        showToast(msg.error, 'error');
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

$('#searchInput').on('input', function () {
    const query = $(this).val().toLowerCase();
    const filtered = allProducts.filter(p => p.name.toLowerCase().includes(query));
    renderProducts(filtered);
});

(async function () {
    await loadCart();
    loadProducts();
    updateCartBadge();
})();

// Page ko'ringanida cart ni qayta yuklash
window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        loadCart();
        renderProducts(allProducts);
    }
});

window.addEventListener('focus', async function () {
    await loadCart();
    renderProducts(allProducts);
});

let searchTimeout;
const searchInput = $('#searchInput');
const clearBtn = $('#clearSearch');

searchInput.on('input', function () {
    const query = $(this).val().trim();

    if (query.length > 0) {
        clearBtn.removeClass('hidden');
    } else {
        clearBtn.addClass('hidden');
        renderProducts(allProducts);
        return;
    }

    if (query.length < 2) return;

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchProductsInCategory(query);
    }, 500);
});

clearBtn.on('click', function () {
    searchInput.val('');
    $(this).addClass('hidden');
    renderProducts(allProducts);
});

async function searchProductsInCategory(query) {
    $('#skeleton-products').removeClass('hidden');
    $('#real-products').addClass('hidden');

    try {
        const response = await fetch(`${apiUrl}/search?q=${encodeURIComponent(query)}&lang=${userLang}&category_id=${categoryId}&user_id=${userId}`);
        const data = await response.json();

        $('#skeleton-products').addClass('hidden');
        $('#real-products').removeClass('hidden');

        if (data.success && data.products.length > 0) {
            renderProducts(data.products);
        } else {
            $('#real-products').html(`
                <div style="grid-column: 1/-1; text-align: center; padding: 40px 20px;">
                    <div style="font-size: 50px; margin-bottom: 10px;">🔍</div>
                    <div style="color: #666;">${msg.no_products}</div>
                </div>
            `);
        }
    } catch (error) {
        console.error('Qidiruv xatosi:', error);
        $('#skeleton-products').addClass('hidden');
        $('#real-products').removeClass('hidden');
    }
}
