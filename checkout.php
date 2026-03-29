<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/shipping.php';

$siteConfig = getSiteConfig();
$pageTitle = 'Checkout | ' . $siteConfig['site_name'];
$bodyClass = 'checkout-page';
$products = getProducts();
$shipping = getShippingSettings();

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container checkout-layout compact-checkout">
        <article class="card checkout-card">
            <h2>Secure Checkout</h2>
            <p class="muted">Please provide delivery details to complete your order.</p>
            <form id="checkout-form" class="checkout-form">
                <div class="checkout-section-title">Customer Information</div>
                <div class="grid two-col">
                    <div class="form-group"><label>Name *</label><input name="name" required></div>
                    <div class="form-group"><label>Phone *</label><input name="phone" required></div>
                </div>
                <div class="form-group"><label>Address *</label><textarea name="address" required></textarea></div>
                <div class="form-group"><label>Email (optional)</label><input name="email" type="email"></div>
                <div class="checkout-section-title">Order Preferences</div>
                <div class="grid two-col">
                    <div class="form-group"><label>Size</label><select name="size" id="checkout-size"><option value="">Select size</option></select></div>
                    <div class="form-group"><label>Color</label><select name="color" id="checkout-color"><option value="">Select color</option></select></div>
                </div>
                <div class="form-group">
                    <label>Delivery Area</label>
                    <select name="delivery_zone" id="checkout-zone">
                        <option value="inside_dhaka">Inside Dhaka (৳<?php echo (int) ($shipping['inside_dhaka_charge_bdt'] ?? 70); ?>)</option>
                        <option value="outside_dhaka">Outside Dhaka (৳<?php echo (int) ($shipping['outside_dhaka_charge_bdt'] ?? 130); ?>)</option>
                    </select>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes"></textarea></div>
                <button class="btn checkout-submit-btn" type="submit">Place Order</button>
                <p id="checkout-msg" class="notice" style="display:none;"></p>
            </form>
        </article>

        <article class="card checkout-summary-card">
            <h3><i class="fa-solid fa-receipt"></i> Order Summary</h3>
            <div id="checkout-summary" class="order-summary muted">Loading cart...</div>
            <p class="muted">Free shipping on order over ৳<?php echo (int) ($shipping['free_shipping_threshold_bdt'] ?? 1500); ?></p>
        </article>
    </div>
</section>

<script>
(() => {
    const productCatalog = <?php echo json_encode(array_values($products), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const productMap = new Map(productCatalog.map((p) => [Number(p.id), p]));
    const threshold = <?php echo (float) ($shipping['free_shipping_threshold_bdt'] ?? 1500); ?>;
    const insideCharge = <?php echo (float) ($shipping['inside_dhaka_charge_bdt'] ?? 70); ?>;
    const outsideCharge = <?php echo (float) ($shipping['outside_dhaka_charge_bdt'] ?? 130); ?>;

    const form = document.getElementById('checkout-form');
    const summary = document.getElementById('checkout-summary');
    const msg = document.getElementById('checkout-msg');
    const zone = document.getElementById('checkout-zone');
    const sizeSelect = document.getElementById('checkout-size');
    const colorSelect = document.getElementById('checkout-color');

    let cart = [];

    function track(eventName, params = {}) { if (typeof window.fbq === 'function') window.fbq('trackCustom', eventName, params); }

    function getFbCookie(name) {
        const v = document.cookie.split('; ').find((row) => row.startsWith(name + '='));
        return v ? decodeURIComponent(v.split('=')[1] || '') : '';
    }

    function generateEventId(prefix = 'evt') {
        return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
    }

    function loadCart() {
        try {
            const raw = JSON.parse(localStorage.getItem('ch_cart') || '[]');
            if (Array.isArray(raw)) {
                cart = raw.filter((i) => Number(i.id) && Number(i.qty) > 0);
            }
        } catch (e) {
            cart = [];
        }
    }

    function buildVariationOptions() {
        const sizes = new Set();
        const colors = new Set();
        cart.forEach((item) => {
            const p = productMap.get(Number(item.id));
            (p?.variations?.sizes || []).forEach((s) => sizes.add(s));
            (p?.variations?.colors || []).forEach((c) => colors.add(c));
        });

        const setOpts = (el, vals, label) => {
            el.innerHTML = `<option value="">Select ${label}</option>`;
            if (!vals.length) {
                el.disabled = true;
                return;
            }
            el.disabled = false;
            vals.forEach((v) => {
                const o = document.createElement('option');
                o.value = v;
                o.textContent = v;
                el.appendChild(o);
            });
        };

        setOpts(sizeSelect, [...sizes], 'size');
        setOpts(colorSelect, [...colors], 'color');
    }

    function totals() {
        const subtotal = cart.reduce((sum, item) => sum + (Number(item.qty) * Number(item.price)), 0);
        let delivery = zone.value === 'outside_dhaka' ? outsideCharge : insideCharge;
        if (subtotal >= threshold) delivery = 0;
        return { subtotal, delivery, grand: subtotal + delivery };
    }

    function renderSummary() {
        if (!cart.length) {
            summary.innerHTML = '<p>Your cart is empty. Please add products first.</p>';
            return;
        }

        const { subtotal, delivery, grand } = totals();
        const rows = cart.map((item) => `<li>${item.name} × ${item.qty} = ৳${Math.round(item.qty * item.price)}</li>`).join('');
        summary.innerHTML = `<ul>${rows}</ul><p>Subtotal: ৳${Math.round(subtotal)}</p><p>Delivery: ৳${Math.round(delivery)}</p><p><strong>Grand Total: ৳${Math.round(grand)}</strong></p>`;
    }

    zone.addEventListener('change', renderSummary);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!cart.length) {
            msg.style.display = 'block';
            msg.textContent = 'Cart is empty. Add products from homepage first.';
            return;
        }

        const payload = Object.fromEntries(new FormData(form).entries());
        payload.cart_items = cart.map((i) => ({ id: Number(i.id), qty: Number(i.qty) }));
        payload.event_id = generateEventId('purchase');
        payload.event_source_url = window.location.href;
        payload.fbc = getFbCookie('_fbc');
        payload.fbp = getFbCookie('_fbp');

        const response = await fetch('submit_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        msg.style.display = 'block';
        msg.textContent = data.message || 'Something went wrong.';

        if (data.success) {
            track('Purchase', { value: data.grand_total_bdt || 0 });
            if (typeof window.fbq === 'function') {
                window.fbq('track', 'Purchase', {currency: 'BDT', value: Number(data.grand_total_bdt || 0)}, {eventID: data.event_id || payload.event_id});
            }
            localStorage.setItem('ch_cart', '[]');
            cart = [];
            renderSummary();
            form.reset();
        }
    });

    loadCart();
    buildVariationOptions();
    renderSummary();
    track('InitiateCheckout', { items: cart.length });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
