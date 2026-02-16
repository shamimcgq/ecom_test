<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/shipping.php';

$siteConfig = getSiteConfig();
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = findProductById($productId);

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found | ' . $siteConfig['site_name'];
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><h2>Product not found</h2><p>Please go back to the shop page.</p></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$shipping = getShippingSettings();
$insideDhakaCharge = (float) ($shipping['inside_dhaka_charge_bdt'] ?? 70);
$outsideDhakaCharge = (float) ($shipping['outside_dhaka_charge_bdt'] ?? 130);

$offerPrice = isset($product['offer_price_bdt']) && (float) $product['offer_price_bdt'] > 0
    ? (float) $product['offer_price_bdt']
    : round((float) $product['price_bdt'] * 0.9, 0);

$pageTitle = $product['title'] . ' | ' . $siteConfig['site_name'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container product-details-layout">
        <div>
            <div class="slide-wrap">
                <?php foreach ($product['images'] as $idx => $img): ?>
                    <img class="slide-image <?php echo $idx === 0 ? 'active' : ''; ?>" src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($product['title']); ?> image <?php echo $idx + 1; ?>">
                <?php endforeach; ?>
                <?php if (count($product['images']) > 1): ?>
                    <button class="slide-btn prev" type="button">‹</button>
                    <button class="slide-btn next" type="button">›</button>
                <?php endif; ?>
            </div>

            <div class="thumb-row">
                <?php foreach ($product['images'] as $idx => $img): ?>
                    <img class="thumb <?php echo $idx === 0 ? 'active' : ''; ?>" data-index="<?php echo $idx; ?>" src="<?php echo htmlspecialchars($img); ?>" alt="thumb <?php echo $idx + 1; ?>">
                <?php endforeach; ?>
            </div>
        </div>

        <article class="card">
            <h1><?php echo htmlspecialchars($product['title']); ?></h1>
            <div class="price-highlight">
                <p class="price current">Price: ৳<?php echo number_format((float) $product['price_bdt'], 0); ?></p>
                <p class="offer-price">Offer: ৳<?php echo number_format($offerPrice, 0); ?></p>
            </div>

            <div class="product-actions">
                <button class="buy-btn detail-buy-now" data-id="<?php echo (int) $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['title']); ?>" data-price="<?php echo (float) $product['price_bdt']; ?>" type="button">Buy Now</button>
                <button class="btn detail-order-now" data-id="<?php echo (int) $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['title']); ?>" data-price="<?php echo (float) $product['price_bdt']; ?>" type="button">Order Now</button>
            </div>

            <?php foreach ($product['description_paragraphs'] as $paragraph): ?>
                <p><?php echo htmlspecialchars($paragraph); ?></p>
            <?php endforeach; ?>

            <div class="variation-box">
                <p><strong>Colors:</strong> <?php echo htmlspecialchars(implode(', ', $product['variations']['colors'] ?? [])); ?></p>
                <p><strong>Sizes:</strong> <?php echo htmlspecialchars(implode(', ', $product['variations']['sizes'] ?? [])); ?></p>
            </div>

            <div class="product-actions">
                <button class="icon-btn detail-add-cart" data-id="<?php echo (int) $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['title']); ?>" data-price="<?php echo (float) $product['price_bdt']; ?>" type="button">🛒 Add to Cart</button>
            </div>
        </article>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2>Detailed Product Images</h2>
        <div class="grid detail-grid">
            <?php foreach ($product['detail_images'] as $item): ?>
                <article class="card detail-image-card">
                    <img class="detail-image" src="<?php echo htmlspecialchars($item['url']); ?>" alt="<?php echo htmlspecialchars($item['caption']); ?>">
                    <p class="muted"><?php echo htmlspecialchars($item['caption']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="floating-cart-bar">
    <div class="floating-cart-main">
        <div>
            <p class="floating-cart-title">🛒 Cart <strong id="detail-cart-count">0</strong></p>
            <p class="floating-cart-subtitle">Continue from your cart</p>
        </div>
        <button id="detail-go-checkout" class="floating-cart-btn" type="button">Checkout</button>
    </div>
</div>


<div id="product-order-modal" class="modal" aria-hidden="true">
    <div class="modal-card checkout-modal-card">
        <div class="modal-header">
            <h3>Place Your Order</h3>
            <button type="button" id="product-close-order-modal" class="close-cart">×</button>
        </div>
        <div id="product-order-summary" class="order-summary"></div>
        <form id="product-order-form" class="order-form">
            <div class="grid two-col">
                <div class="form-group"><label>Name *</label><input name="name" required></div>
                <div class="form-group"><label>Phone *</label><input name="phone" required></div>
            </div>
            <div class="form-group"><label>Address *</label><textarea name="address" required></textarea></div>
            <div class="form-group"><label>Email (optional)</label><input name="email" type="email"></div>
            <div class="grid two-col">
                <div class="form-group"><label>Size</label><select name="size" id="product-order-size"><option value="">Select size</option></select></div>
                <div class="form-group"><label>Color</label><select name="color" id="product-order-color"><option value="">Select color</option></select></div>
            </div>
            <div class="form-group"><label>Delivery Area</label><select name="delivery_zone" id="product-delivery-zone"><option value="inside_dhaka">Inside Dhaka</option><option value="outside_dhaka">Outside Dhaka</option></select></div>
            <div class="form-group"><label>Notes</label><textarea name="notes"></textarea></div>
            <button class="btn" type="submit">Submit Order</button>
        </form>
    </div>
</div>

<div id="product-toast" class="toast" role="status" aria-live="polite"></div>
<script>
(() => {
    const slides = Array.from(document.querySelectorAll('.slide-image'));
    const thumbs = Array.from(document.querySelectorAll('.thumb'));
    let current = 0;
    let startX = 0;

    const INSIDE_DHAKA = <?php echo (float) $insideDhakaCharge; ?>;
    const OUTSIDE_DHAKA = <?php echo (float) $outsideDhakaCharge; ?>;

    const modal = document.getElementById('product-order-modal');
    const closeModalBtn = document.getElementById('product-close-order-modal');
    const orderSummary = document.getElementById('product-order-summary');
    const orderForm = document.getElementById('product-order-form');
    const zone = document.getElementById('product-delivery-zone');
    const sizeSelect = document.getElementById('product-order-size');
    const colorSelect = document.getElementById('product-order-color');
    const toast = document.getElementById('product-toast');

    const productInfo = {
        id: Number(<?php echo (int) $product['id']; ?>),
        name: <?php echo json_encode($product['title']); ?>,
        price: Number(<?php echo (float) $product['price_bdt']; ?>),
        sizes: <?php echo json_encode(array_values($product['variations']['sizes'] ?? [])); ?>,
        colors: <?php echo json_encode(array_values($product['variations']['colors'] ?? [])); ?>,
    };

    let currentOrderItems = [];

    function track(eventName, params = {}) {
        if (typeof window.fbq === 'function') window.fbq('trackCustom', eventName, params);
    }

    function showToast(message, type = 'success') {
        toast.textContent = message;
        toast.className = `toast show ${type}`;
        setTimeout(() => { toast.className = 'toast'; }, 2400);
    }

    function showSlide(index) {
        if (!slides.length) return;
        current = (index + slides.length) % slides.length;
        slides.forEach((el, idx) => el.classList.toggle('active', idx === current));
        thumbs.forEach((el, idx) => el.classList.toggle('active', idx === current));
    }

    const prev = document.querySelector('.slide-btn.prev');
    const next = document.querySelector('.slide-btn.next');
    if (prev) prev.addEventListener('click', () => showSlide(current - 1));
    if (next) next.addEventListener('click', () => showSlide(current + 1));
    thumbs.forEach((thumb, idx) => thumb.addEventListener('click', () => showSlide(idx)));

    const slideWrap = document.querySelector('.slide-wrap');
    if (slideWrap) {
        slideWrap.addEventListener('touchstart', (e) => { startX = e.changedTouches[0].clientX; }, { passive: true });
        slideWrap.addEventListener('touchend', (e) => {
            const diff = e.changedTouches[0].clientX - startX;
            if (Math.abs(diff) < 35) return;
            if (diff < 0) showSlide(current + 1); else showSlide(current - 1);
        }, { passive: true });
    }

    function getCart() {
        try {
            const cart = JSON.parse(localStorage.getItem('ch_cart') || '[]');
            return Array.isArray(cart) ? cart : [];
        } catch (error) { return []; }
    }

    function setCart(cart) {
        localStorage.setItem('ch_cart', JSON.stringify(cart));
        const count = cart.reduce((sum, item) => sum + Number(item.qty || 0), 0);
        document.getElementById('detail-cart-count').textContent = count;
    }

    function addToCart() {
        const cart = getCart();
        const existing = cart.find((item) => Number(item.id) === productInfo.id);
        if (existing) existing.qty += 1;
        else cart.push({ id: productInfo.id, name: productInfo.name, price: productInfo.price, qty: 1 });
        setCart(cart);
        showToast('Added to cart');
        track('AddToCart', { product_id: productInfo.id, value: productInfo.price });
    }

    function renderVariationOptions() {
        const setSelect = (select, values, label) => {
            select.innerHTML = `<option value="">Select ${label}</option>`;
            if (!values.length) {
                select.disabled = true;
                return;
            }
            select.disabled = false;
            values.forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                select.appendChild(option);
            });
        };

        setSelect(sizeSelect, productInfo.sizes, 'size');
        setSelect(colorSelect, productInfo.colors, 'color');
    }

    function renderOrderSummary() {
        const subtotal = currentOrderItems.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const delivery = zone.value === 'outside_dhaka' ? OUTSIDE_DHAKA : INSIDE_DHAKA;
        const total = subtotal + delivery;
        const lines = currentOrderItems.map((item) => `<li>${item.name} × ${item.qty} = ৳${Math.round(item.price * item.qty)}</li>`).join('');
        orderSummary.innerHTML = `<strong>Order Summary</strong><ul>${lines}</ul><p>Subtotal: ৳${Math.round(subtotal)}</p><p>Delivery: ৳${Math.round(delivery)}</p><p><strong>Total: ৳${Math.round(total)}</strong></p>`;
    }

    function openOrderModal(items) {
        currentOrderItems = items.map((item) => ({ ...item }));
        renderVariationOptions();
        renderOrderSummary();
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        track('InitiateCheckout', { items: currentOrderItems.length });
    }

    function closeOrderModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    zone.addEventListener('change', renderOrderSummary);
    closeModalBtn.addEventListener('click', closeOrderModal);
    modal.addEventListener('click', (event) => { if (event.target === modal) closeOrderModal(); });

    document.querySelector('.detail-add-cart')?.addEventListener('click', addToCart);
    document.querySelector('.detail-buy-now')?.addEventListener('click', () => {
        openOrderModal([{ id: productInfo.id, name: productInfo.name, price: productInfo.price, qty: 1 }]);
        track('BuyNowClick', { product_id: productInfo.id, value: productInfo.price });
    });
    document.querySelector('.detail-order-now')?.addEventListener('click', () => {
        openOrderModal([{ id: productInfo.id, name: productInfo.name, price: productInfo.price, qty: 1 }]);
    });

    document.getElementById('detail-go-checkout').addEventListener('click', () => {
        const cart = getCart();
        if (!cart.length) {
            showToast('Cart is empty', 'error');
            return;
        }
        openOrderModal(cart.map((item) => ({ id: Number(item.id), name: item.name, price: Number(item.price), qty: Number(item.qty) })));
    });

    orderForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!currentOrderItems.length) {
            showToast('No products selected', 'error');
            return;
        }

        const payload = Object.fromEntries(new FormData(orderForm).entries());
        payload.cart_items = currentOrderItems.map((item) => ({ id: item.id, qty: item.qty }));

        try {
            const response = await fetch('submit_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (!data.success) {
                showToast(data.message || 'Failed to submit order', 'error');
                return;
            }
            track('Purchase', { value: data.grand_total_bdt || 0, order_id: data.order_id || 0 });
            showToast(`Order submitted (#${data.order_id})`);
            closeOrderModal();
            orderForm.reset();

            const cart = getCart();
            const usingAllCart = currentOrderItems.length === cart.length && currentOrderItems.every((item, idx) => cart[idx] && Number(cart[idx].id) === Number(item.id));
            if (usingAllCart) setCart([]);
        } catch (error) {
            showToast('Network error, please try again', 'error');
        }
    });

    setCart(getCart());
    showSlide(0);
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
