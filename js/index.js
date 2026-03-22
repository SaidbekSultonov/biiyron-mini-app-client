const userId = $('#telegram_user_id').attr('data-id');
const userLang = $('#user_lang').attr('data-lang');
const warehouseId = $('#warehouse_id').attr('data-id');

let searchTimeout;
const searchInput = $('#searchInput');
const clearBtn = $('#clearSearch');
const realCategories = $('#real-categories');
const skeletonCategories = $('#skeleton-categories');

searchInput.on('input', function() {
    const query = $(this).val().trim();
    
    if (query.length > 0) {
        clearBtn.removeClass('hidden');
    } else {
        clearBtn.addClass('hidden');
        showCategories();
        return;
    }
    
    if (query.length < 2) return;
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchProducts(query);
    }, 500);
});

clearBtn.on('click', function() {
    searchInput.val('');
    $(this).addClass('hidden');
    showCategories();
});

async function searchProducts(query) {
    const container = $('#real-categories');
    container.empty();
    $('#skeleton-categories').removeClass('hidden');
    container.addClass('hidden');
    
    try {
        const response = await fetch(`${apiUrl}/search?q=${encodeURIComponent(query)}&lang=${userLang}&user_id=${userId}`);
        const data = await response.json();
        
        $('#skeleton-categories').addClass('hidden');
        container.removeClass('hidden');
        
        if (data.success && data.products.length > 0) {
            showSearchResults(data.products);
        } else {
            showNoResults();
        }
    } catch (error) {
        console.error('Qidiruv xatosi:', error);
        $('#skeleton-categories').addClass('hidden');
        container.removeClass('hidden');
        showNoResults();
    }
}

function showSearchResults(products) {
    allProducts = products; // Global o'zgaruvchiga saqlaymiz
    const container = $('#real-categories');
    container.empty();
    container.removeClass('categories').addClass('products-grid');
    
    products.forEach(product => {
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
}

let allProducts = [];
let cartItems = {};

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

function openProduct(id) {
    window.location = `product-detail.php?product_id=${id}&lang=${userLang}`;
}

async function addToCart(productId) {
    try {
        const response = await fetch(`${apiUrl}/cart/add`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
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
            searchProducts(searchInput.val().trim());
            updateCartBadge();
        } else {
            showToast(data.message || msg.error, 'error');
        }
    } catch (error) {
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
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    cart_id: cartItem.cart_id,
                    quantity: cartItem.quantity - 1,
                    lang: userLang
                })
            });

            if ((await response.json()).success) {
                await loadCart();
                searchProducts(searchInput.val().trim());
                updateCartBadge();
            }
        } else {
            const response = await fetch(`${apiUrl}/cart/remove`, {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ cart_id: cartItem.cart_id, lang: userLang })
            });

            if ((await response.json()).success) {
                await loadCart();
                searchProducts(searchInput.val().trim());
                updateCartBadge();
            }
        }
    } catch (error) {
        showToast(msg.error, 'error');
    }
}

function showToast(message, type = 'success') {
    const toast = $(`<div class="toast toast-${type}">${message}</div>`);
    $('body').append(toast);
    setTimeout(() => toast.addClass('show'), 100);
    setTimeout(() => {
        toast.removeClass('show');
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

function showNoResults() {
    $('#real-categories').html(`
        <div style="grid-column: 1/-1; text-align: center; padding: 40px 20px;">
            <div style="font-size: 50px; margin-bottom: 10px;">🔍</div>
            <div style="color: #666;">${msg.no_products}</div>
        </div>
    `);
}

function showCategories() {
    const container = $('#real-categories');
    container.empty(); // Avval tozalaymiz
    $('#skeleton-categories').removeClass('hidden'); // Skeleton ko'rsatamiz
    container.removeClass('products-grid').addClass('categories');
    loadCategories();
}

const colors = [
    ['#FFB3BA', '#FF8FA3'],
    ['#BAE1FF', '#88C9FF'],
    ['#FFD9A3', '#FFC170'],
    ['#B5EAB3', '#8FD98F'],
    ['#E0B3FF', '#C88FFF'],
    ['#FFB3D9', '#FF8FC7'],
    ['#B3F0E6', '#8FE6D9'],
    ['#FFB8B8', '#FF9696'],
    ['#D4F0B3', '#B8E68F'],
    ['#C5CAE9', '#9FA8DA'],
    ['#FFF4A3', '#FFE770'],
    ['#B3E5FC', '#81D4FA'],
    ['#FFCCBC', '#FFAB91'],
    ['#D1C4E9', '#B39DDB'],
    ['#BBDEFB', '#90CAF9'],
    ['#D7CCC8', '#BCAAA4'],
    ['#FFE0B2', '#FFCC80'],
    ['#F8BBD0', '#F48FB1'],
    ['#C8E6C9', '#A5D6A7']
];

async function loadCategories() {
    try {
        await loadCart(); // Cart ni yuklaymiz
        
        const response = await fetch(`${apiUrl}/categories?lang=${userLang}`);
        const data = await response.json();
        
        const container = $('#real-categories');
        container.empty();
        
        data.categories.forEach((category) => {
            const randomColor = colors[Math.floor(Math.random() * colors.length)];
            
            container.append(`
                <div class="category-card" onclick="window.location='products.php?category_id=${category.id}'">
                    <div class="category-icon-wrapper" style="background: linear-gradient(135deg, ${randomColor[0]}, ${randomColor[1]});">
                        <img src="${apiUrl.replace('/api', '')}/${category.image}" alt="${category.name}" class="category-icon">
                    </div>
                    <div class="category-name">${category.name}</div>
                </div>
            `);
        });
        
        $('#skeleton-categories').addClass('hidden');
        container.removeClass('hidden');
        
    } catch (error) {
        console.error('Xato:', error);
    }
}


loadCategories();

// Slider
const slider = $('#slider');
const dots = $('.dot');

slider.on('scroll', function() {
    const index = Math.round(this.scrollLeft / this.offsetWidth);
    dots.removeClass('active').eq(index).addClass('active');
});

let currentSlide = 0;
setInterval(() => {
    currentSlide = (currentSlide + 1) % 3;
    slider[0].scrollTo({
        left: slider[0].offsetWidth * currentSlide,
        behavior: 'smooth'
    });
}, 3000);


(async function() {
    await loadCart();
    await loadCategories();
    updateCartBadge();
})();