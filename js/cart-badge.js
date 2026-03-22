// apiUrl — bot/config.php dan PHP orqali har bir sahifada inject qilinadi

async function updateCartBadge() {
    const chatId = $('#telegram_user_id').attr('data-id');
    const userLang = $('#user_lang').attr('data-lang');
    
    if (!chatId) return;
    
    try {
        const response = await fetch(`${apiUrl}/cart?client_id=${chatId}&lang=${userLang}`);
        const data = await response.json();
        
        if (data.success) {
            $('.cart-badge').text(data.items.length);
        }
    } catch (error) {
        console.error('Cart yuklanmadi:', error);
    }
}