<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

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
            <p class="price">৳<?php echo number_format((float) $product['price_bdt'], 0); ?></p>

            <?php foreach ($product['description_paragraphs'] as $paragraph): ?>
                <p><?php echo htmlspecialchars($paragraph); ?></p>
            <?php endforeach; ?>

            <div class="variation-box">
                <p><strong>Colors:</strong> <?php echo htmlspecialchars(implode(', ', $product['variations']['colors'] ?? [])); ?></p>
                <p><strong>Sizes:</strong> <?php echo htmlspecialchars(implode(', ', $product['variations']['sizes'] ?? [])); ?></p>
            </div>

            <div class="product-actions">
                <button class="icon-btn detail-add-cart" data-id="<?php echo (int) $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['title']); ?>" data-price="<?php echo (float) $product['price_bdt']; ?>" type="button">🛒 Add to Cart</button>
                <button class="buy-btn detail-buy-now" data-id="<?php echo (int) $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['title']); ?>" data-price="<?php echo (float) $product['price_bdt']; ?>" type="button">Buy Now</button>
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

<script>
(() => {
    const slides = Array.from(document.querySelectorAll('.slide-image'));
    const thumbs = Array.from(document.querySelectorAll('.thumb'));
    let current = 0;
    let startX = 0;

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
            const endX = e.changedTouches[0].clientX;
            const diff = endX - startX;
            if (Math.abs(diff) < 35) return;
            if (diff < 0) showSlide(current + 1); else showSlide(current - 1);
        }, { passive: true });
    }

    function track(eventName, params = {}) { if (typeof window.fbq === 'function') window.fbq('trackCustom', eventName, params); }

    function getCart() {
        try {
            const cart = JSON.parse(localStorage.getItem('ch_cart') || '[]');
            return Array.isArray(cart) ? cart : [];
        } catch (e) {
            return [];
        }
    }

    function setCart(cart) {
        localStorage.setItem('ch_cart', JSON.stringify(cart));
        const count = cart.reduce((s, i) => s + Number(i.qty || 0), 0);
        document.getElementById('detail-cart-count').textContent = count;
    }

    function addToCart(product, buyNow) {
        const cart = getCart();
        const existing = cart.find((i) => Number(i.id) === Number(product.id));
        if (existing) existing.qty += 1;
        else cart.push({ ...product, qty: 1 });
        setCart(cart);
        track(buyNow ? 'BuyNowClick' : 'AddToCart', { product_id: product.id, value: product.price });
        if (buyNow) window.location.href = 'checkout.php';
        else alert('Product added to cart.');
    }

    const addBtn = document.querySelector('.detail-add-cart');
    const buyBtn = document.querySelector('.detail-buy-now');
    if (addBtn) addBtn.addEventListener('click', () => addToCart({ id: Number(addBtn.dataset.id), name: addBtn.dataset.name, price: Number(addBtn.dataset.price) }, false));
    if (buyBtn) buyBtn.addEventListener('click', () => addToCart({ id: Number(buyBtn.dataset.id), name: buyBtn.dataset.name, price: Number(buyBtn.dataset.price) }, true));

    document.getElementById('detail-go-checkout').addEventListener('click', () => {
        track('InitiateCheckout', { source: 'product_page' });
        window.location.href = 'checkout.php';
    });

    setCart(getCart());
    showSlide(0);
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
