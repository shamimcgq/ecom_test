<?php
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/shipping.php';
require_once __DIR__ . '/includes/pixel.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$name = trim((string) ($input['name'] ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$address = trim((string) ($input['address'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$size = trim((string) ($input['size'] ?? ''));
$color = trim((string) ($input['color'] ?? ''));
$notes = trim((string) ($input['notes'] ?? ''));
$deliveryZone = trim((string) ($input['delivery_zone'] ?? 'inside_dhaka'));
$cartItems = $input['cart_items'] ?? [];
$eventId = trim((string) ($input['event_id'] ?? ''));
$eventSourceUrl = trim((string) ($input['event_source_url'] ?? ''));
$fbc = trim((string) ($input['fbc'] ?? ''));
$fbp = trim((string) ($input['fbp'] ?? ''));

if ($name === '' || $phone === '' || $address === '' || !is_array($cartItems) || empty($cartItems)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please fill required fields and add products.']);
    exit;
}

$productMap = [];
foreach (getProducts() as $product) {
    $productMap[(int) ($product['id'] ?? 0)] = $product;
}

$normalizedItems = [];
$subtotal = 0;
foreach ($cartItems as $item) {
    $id = (int) ($item['id'] ?? 0);
    $qty = max(1, (int) ($item['qty'] ?? 1));

    if (!isset($productMap[$id])) {
        continue;
    }

    $product = $productMap[$id];
    $unitPrice = (float) ($product['price_bdt'] ?? 0);
    $lineTotal = $unitPrice * $qty;
    $subtotal += $lineTotal;

    $normalizedItems[] = [
        'id' => $id,
        'title' => $product['title'] ?? 'Product',
        'qty' => $qty,
        'unit_price_bdt' => $unitPrice,
        'line_total_bdt' => $lineTotal,
    ];
}

if (empty($normalizedItems)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'No valid products found in cart.']);
    exit;
}

$shippingSettings = getShippingSettings();
$threshold = (float) ($shippingSettings['free_shipping_threshold_bdt'] ?? 1500);
$insideCharge = (float) ($shippingSettings['inside_dhaka_charge_bdt'] ?? 70);
$outsideCharge = (float) ($shippingSettings['outside_dhaka_charge_bdt'] ?? 130);
$deliveryCharge = $deliveryZone === 'outside_dhaka' ? $outsideCharge : $insideCharge;

if ($subtotal >= $threshold) {
    $deliveryCharge = 0;
}

$grandTotal = $subtotal + $deliveryCharge;

$order = addOrder([
    'customer_name' => $name,
    'phone' => $phone,
    'address' => $address,
    'email' => $email,
    'size' => $size,
    'color' => $color,
    'notes' => $notes,
    'delivery_zone' => $deliveryZone,
    'delivery_charge_bdt' => $deliveryCharge,
    'subtotal_bdt' => $subtotal,
    'grand_total_bdt' => $grandTotal,
    'items' => $normalizedItems,
    'status' => 'pending',
]);

$finalEventId = $eventId !== '' ? $eventId : ('order-' . ($order['id'] ?? ''));
sendServerSidePurchaseEvent($order, [
    'event_id' => $finalEventId,
    'event_source_url' => $eventSourceUrl,
    'fbc' => $fbc,
    'fbp' => $fbp,
]);

echo json_encode([
    'success' => true,
    'message' => 'Order submitted successfully. Our team will contact you shortly.',
    'subtotal_bdt' => round($subtotal),
    'delivery_charge_bdt' => round($deliveryCharge),
    'grand_total_bdt' => round($grandTotal),
    'event_id' => $finalEventId,
]);
