<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/shipping.php';

$siteConfig = getSiteConfig();
$pageTitle = 'Home | ' . $siteConfig['site_name'];

$products = getProducts();
$shippingSettings = getShippingSettings();
$freeShippingThreshold = (float) ($shippingSettings['free_shipping_threshold_bdt'] ?? 1500);
$insideDhakaCharge = (float) ($shippingSettings['inside_dhaka_charge_bdt'] ?? 70);
$outsideDhakaCharge = (float) ($shippingSettings['outside_dhaka_charge_bdt'] ?? 130);

$searchQuery = trim((string) ($_GET['q'] ?? ''));
if ($searchQuery !== '') {
    $products = array_values(array_filter($products, function (array $product) use ($searchQuery): bool {
        $needle = mb_strtolower($searchQuery);
        $title = mb_strtolower((string) ($product['title'] ?? ''));
        $desc = mb_strtolower((string) ($product['short_description'] ?? ''));
        return str_contains($title, $needle) || str_contains($desc, $needle);
    }));
}

$perPage = 20;
$totalProducts = count($products);
$totalPages = (int) ceil($totalProducts / $perPage);
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$currentPage = min($currentPage, max($totalPages, 1));
$offset = ($currentPage - 1) * $perPage;
$productsForPage = array_slice($products, $offset, $perPage);

$services = [
    ['title' => 'Wholesale from China', 'description' => 'Bulk sourcing support with supplier negotiation and quality checks.', 'icon' => '📦', 'image' => 'https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?auto=format&fit=crop&w=700&q=80'],
    ['title' => 'Retail from China', 'description' => 'Flexible small quantity purchase for growing online sellers.', 'icon' => '🛍️', 'image' => 'https://images.unsplash.com/photo-1607082350899-7e105aa886ae?auto=format&fit=crop&w=700&q=80'],
    ['title' => 'Door to Door', 'description' => 'End-to-end logistics from supplier pickup to your destination.', 'icon' => '🚚', 'image' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=700&q=80'],
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="banner" style="background-image: url('<?php echo htmlspecialchars($siteConfig['banner_image']); ?>');">
    <div class="banner-content">
        <h1><?php echo htmlspecialchars($siteConfig['banner_heading']); ?></h1>
        <p><?php echo htmlspecialchars($siteConfig['banner_subheading']); ?></p>
        <a href="#services" class="btn">Explore Services</a>
    </div>
</section>

<section class="section" id="services">
    <div class="container">
        <h2>What Services We Are Offering</h2>
        <div class="grid service-grid">
            <?php foreach ($services as $service): ?>
                <article class="card service-card">
                    <img class="service-image" src="<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>">
                    <div class="service-content">
                        <div class="service-pill"><?php echo htmlspecialchars($service['icon']); ?> Verified</div>
                        <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                        <p><?php echo htmlspecialchars($service['description']); ?></p>
                        <a class="btn" href="inquiry.php?service=<?php echo urlencode($service['title']); ?>">Send Query</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section shop-section" id="shop">
    <div class="container">
        <div class="shop-head">
            <h2>Shop Products</h2>
            <button id="open-cart" class="cart-chip" type="button">🛒 Cart <span id="cart-count">0</span></button>
        </div>

        <form method="get" action="index.php#shop" class="search-form">
            <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search products by name or details">
            <button type="submit" class="btn">Search</button>
        </form>

        <p>Products are shown 20 per page. Click any product for full details.</p>

        <div class="grid product-grid">
            <?php foreach ($productsForPage as $product): ?>
                <article class="card product">
                    <a href="product.php?id=<?php echo (int) $product['id']; ?>">
                        <img src="<?php echo htmlspecialchars($product['images'][0]); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                    </a>
                    <h3><a class="product-title" href="product.php?id=<?php echo (int) $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></a></h3>
                    <p><?php echo htmlspecialchars($product['short_description']); ?></p>
                    <p class="price">৳<?php echo number_format((float) $product['price_bdt'], 0); ?></p>
                    <div class="product-actions">
                        <button type="button" class="icon-btn add-cart-btn" data-id="<?php echo (int) $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['title']); ?>" data-price="<?php echo (float) $product['price_bdt']; ?>"><span>🛒</span> Add to Cart</button>
                        <button type="button" class="buy-btn" data-id="<?php echo (int) $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['title']); ?>" data-price="<?php echo (float) $product['price_bdt']; ?>">Buy Now</button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                    <a href="index.php?page=<?php echo $page; ?>&q=<?php echo urlencode($searchQuery); ?>#shop" class="<?php echo $currentPage === $page ? 'active' : ''; ?>"><?php echo $page; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div id="floating-cart-bar" class="floating-cart-bar" role="region" aria-label="Floating cart summary">
    <div class="floating-cart-main">
        <div>
            <p class="floating-cart-title">🛒 Cart <strong id="floating-count">0</strong></p>
            <p class="floating-cart-subtitle">Total: <strong id="floating-total">৳0</strong></p>
        </div>
        <button id="floating-cart-btn" type="button" class="floating-cart-btn">Open Cart</button>
    </div>
    <div class="free-progress-wrap">
        <div class="free-progress-label" id="floating-shipping-note">Add ৳<?php echo (int) round($freeShippingThreshold); ?> to get free shipping</div>
        <div class="free-progress-track"><div id="floating-shipping-progress" class="free-progress-fill" style="width:0%"></div></div>
    </div>
</div>

<aside id="cart-drawer" class="cart-drawer" aria-hidden="true">
    <div class="cart-header"><h3>Your Cart</h3><button id="close-cart" type="button" class="close-cart">×</button></div>
    <div id="cart-items" class="cart-items"><p class="muted">No products added yet.</p></div>
    <div class="cart-footer">
        <p>Total: <strong id="cart-total">৳0</strong></p>
        <p id="shipping-note" class="shipping-note">Add ৳<?php echo (int) round($freeShippingThreshold); ?> for free shipping</p>
        <button id="checkout-btn" class="btn" type="button">Go To Checkout</button>
    </div>
</aside>

<script>
(() => {
    const FREE_SHIPPING_THRESHOLD = <?php echo (float) $freeShippingThreshold; ?>;
    const cart = [];

    const cartCount = document.getElementById('cart-count');
    const floatingCount = document.getElementById('floating-count');
    const cartDrawer = document.getElementById('cart-drawer');
    const cartItemsNode = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const shippingNote = document.getElementById('shipping-note');
    const floatingShippingNote = document.getElementById('floating-shipping-note');
    const floatingTotal = document.getElementById('floating-total');
    const floatingShippingProgress = document.getElementById('floating-shipping-progress');

    function track(eventName, params = {}) { if (typeof window.fbq === 'function') window.fbq('trackCustom', eventName, params); }

    function updateCartView() {
        const count = cart.reduce((sum, item) => sum + item.qty, 0);
        cartCount.textContent = count;
        floatingCount.textContent = count;

        if (!cart.length) {
            cartItemsNode.innerHTML = '<p class="muted">No products added yet.</p>';
            cartTotal.textContent = '৳0';
            shippingNote.textContent = `Add ৳${FREE_SHIPPING_THRESHOLD} for free shipping`;
            floatingShippingNote.textContent = `Add ৳${FREE_SHIPPING_THRESHOLD} to get free shipping`;
            floatingShippingProgress.style.width = '0%';
            floatingTotal.textContent = '৳0';
            localStorage.setItem('ch_cart', JSON.stringify(cart));
            return;
        }

        let total = 0;
        cartItemsNode.innerHTML = cart.map((item) => {
            const lineTotal = item.qty * item.price;
            total += lineTotal;
            return `<div class="cart-line"><div><strong>${item.name}</strong><br><small>৳${Math.round(item.price)} each</small></div><div class="qty-wrap"><button type="button" class="qty-btn" data-action="minus" data-id="${item.id}">-</button><span>${item.qty}</span><button type="button" class="qty-btn" data-action="plus" data-id="${item.id}">+</button><button type="button" class="qty-btn remove" data-action="remove" data-id="${item.id}">×</button></div></div>`;
        }).join('');

        cartTotal.textContent = `৳${Math.round(total)}`;
        floatingTotal.textContent = `৳${Math.round(total)}`;

        if (total >= FREE_SHIPPING_THRESHOLD) {
            shippingNote.textContent = '✅ Free shipping enabled';
            floatingShippingNote.textContent = '✅ Free shipping enabled';
            floatingShippingProgress.style.width = '100%';
            floatingShippingProgress.classList.add('free-enabled');
        } else {
            const remaining = Math.round(FREE_SHIPPING_THRESHOLD - total);
            const progress = Math.max(0, Math.min(100, (total / FREE_SHIPPING_THRESHOLD) * 100));
            shippingNote.textContent = `Add ৳${remaining} for free shipping`;
            floatingShippingNote.textContent = `Add ৳${remaining} to get free shipping`;
            floatingShippingProgress.style.width = `${progress}%`;
            floatingShippingProgress.classList.remove('free-enabled');
        }

        localStorage.setItem('ch_cart', JSON.stringify(cart));
    }

    function addToCart(id, name, price, qty = 1) {
        const numericId = Number(id);
        const numericPrice = Number(price);
        const existing = cart.find((item) => item.id === numericId);
        if (existing) existing.qty += qty;
        else cart.push({ id: numericId, name, price: numericPrice, qty });
        updateCartView();
        track('AddToCart', { product_id: numericId, value: numericPrice });
    }

    function updateQty(id, action) {
        const item = cart.find((i) => i.id === Number(id));
        if (!item) return;
        if (action === 'plus') item.qty += 1;
        if (action === 'minus') item.qty = Math.max(1, item.qty - 1);
        if (action === 'remove') {
            const idx = cart.findIndex((i) => i.id === Number(id));
            if (idx > -1) cart.splice(idx, 1);
        }
        updateCartView();
        track('CartUpdated', { action, product_id: Number(id) });
    }

    function openCart() { cartDrawer.classList.add('open'); cartDrawer.setAttribute('aria-hidden', 'false'); track('CartOpen'); }
    function closeCart() { cartDrawer.classList.remove('open'); cartDrawer.setAttribute('aria-hidden', 'true'); }

    document.querySelectorAll('.add-cart-btn').forEach((button) => button.addEventListener('click', () => addToCart(button.dataset.id, button.dataset.name, button.dataset.price, 1)));
    document.querySelectorAll('.buy-btn').forEach((button) => button.addEventListener('click', () => {
        addToCart(button.dataset.id, button.dataset.name, button.dataset.price, 1);
        window.location.href = 'checkout.php';
    }));

    cartItemsNode.addEventListener('click', (event) => {
        const button = event.target.closest('.qty-btn');
        if (!button) return;
        updateQty(button.dataset.id, button.dataset.action);
    });

    document.getElementById('open-cart').addEventListener('click', openCart);
    document.getElementById('floating-cart-btn').addEventListener('click', openCart);
    document.getElementById('close-cart').addEventListener('click', closeCart);
    document.getElementById('checkout-btn').addEventListener('click', () => { window.location.href = 'checkout.php'; });

    try {
        const saved = JSON.parse(localStorage.getItem('ch_cart') || '[]');
        if (Array.isArray(saved)) saved.forEach((item) => { if (item && Number(item.id) && Number(item.qty) > 0) cart.push({ id: Number(item.id), name: item.name, price: Number(item.price), qty: Number(item.qty) }); });
    } catch (error) {}

    updateCartView();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
