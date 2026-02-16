<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/shipping.php';

$siteConfig = getSiteConfig();
$pageTitle = 'Admin Panel | ' . $siteConfig['site_name'];
$notice = '';
$tab = $_GET['tab'] ?? 'products';

function loadUsers(): array
{
    $path = __DIR__ . '/data/users.json';
    if (!file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }

    $users = json_decode((string) file_get_contents($path), true);
    return is_array($users) ? $users : [];
}

function saveUsers(array $users): void
{
    file_put_contents(__DIR__ . '/data/users.json', json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

$products = getProducts();
$orders = getOrders();
$users = loadUsers();
$shipping = getShippingSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_banner') {
        $siteConfig['site_name'] = trim($_POST['site_name'] ?? $siteConfig['site_name']);
        $siteConfig['logo_url'] = trim($_POST['logo_url'] ?? $siteConfig['logo_url']);
        $siteConfig['whatsapp_number'] = trim($_POST['whatsapp_number'] ?? $siteConfig['whatsapp_number']);
        $siteConfig['fb_pixel_id'] = trim($_POST['fb_pixel_id'] ?? $siteConfig['fb_pixel_id']);
        $siteConfig['banner_heading'] = trim($_POST['banner_heading'] ?? $siteConfig['banner_heading']);
        $siteConfig['banner_subheading'] = trim($_POST['banner_subheading'] ?? $siteConfig['banner_subheading']);
        $siteConfig['banner_image'] = trim($_POST['banner_image'] ?? $siteConfig['banner_image']);
        saveSiteConfig($siteConfig);
        $notice = 'Branding settings updated.';
        $tab = 'banner';
    }

    if ($action === 'add_product') {
        $newId = empty($products) ? 1 : (max(array_column($products, 'id')) + 1);
        $products[] = [
            'id' => $newId,
            'title' => trim($_POST['title'] ?? 'New Product'),
            'price_bdt' => (float) ($_POST['price_bdt'] ?? 0),
            'cost_bdt' => (float) ($_POST['cost_bdt'] ?? 0),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'description_paragraphs' => [trim($_POST['description'] ?? '')],
            'images' => [trim($_POST['image'] ?? 'https://picsum.photos/seed/new-product/800/500')],
            'detail_images' => [['url' => trim($_POST['image'] ?? 'https://picsum.photos/seed/new-product/700/450'), 'caption' => 'Primary view']],
            'variations' => [
                'colors' => array_values(array_filter(array_map('trim', explode(',', (string) ($_POST['colors'] ?? ''))))),
                'sizes' => array_values(array_filter(array_map('trim', explode(',', (string) ($_POST['sizes'] ?? ''))))),
            ],
        ];
        saveProducts($products);
        $notice = 'Product added.';
        $tab = 'products';
    }

    if ($action === 'delete_product') {
        $id = (int) ($_POST['id'] ?? 0);
        $products = array_values(array_filter($products, fn($p) => (int) $p['id'] !== $id));
        saveProducts($products);
        $notice = 'Product deleted.';
        $tab = 'products';
    }

    if ($action === 'update_product') {
        $id = (int) ($_POST['id'] ?? 0);
        foreach ($products as &$product) {
            if ((int) $product['id'] === $id) {
                $product['title'] = trim($_POST['title'] ?? $product['title']);
                $product['price_bdt'] = (float) ($_POST['price_bdt'] ?? $product['price_bdt']);
                $product['cost_bdt'] = (float) ($_POST['cost_bdt'] ?? $product['cost_bdt']);
                $product['short_description'] = trim($_POST['short_description'] ?? $product['short_description']);
            }
        }
        unset($product);
        saveProducts($products);
        $notice = 'Product updated.';
        $tab = 'products';
    }

    if ($action === 'add_user') {
        $users[] = [
            'id' => empty($users) ? 1 : max(array_column($users, 'id')) + 1,
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => trim($_POST['role'] ?? 'customer'),
        ];
        saveUsers($users);
        $notice = 'User added.';
        $tab = 'users';
    }

    if ($action === 'delete_user') {
        $id = (int) ($_POST['id'] ?? 0);
        $users = array_values(array_filter($users, fn($u) => (int) $u['id'] !== $id));
        saveUsers($users);
        $notice = 'User deleted.';
        $tab = 'users';
    }

    if ($action === 'save_shipping') {
        $shipping['free_shipping_threshold_bdt'] = (float) ($_POST['free_shipping_threshold_bdt'] ?? $shipping['free_shipping_threshold_bdt']);
        $shipping['inside_dhaka_charge_bdt'] = (float) ($_POST['inside_dhaka_charge_bdt'] ?? $shipping['inside_dhaka_charge_bdt']);
        $shipping['outside_dhaka_charge_bdt'] = (float) ($_POST['outside_dhaka_charge_bdt'] ?? $shipping['outside_dhaka_charge_bdt']);
        saveShippingSettings($shipping);
        $notice = 'Shipping settings updated.';
        $tab = 'shipping';
    }

    if ($action === 'update_order_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'pending');
        foreach ($orders as &$order) {
            if ((int) ($order['id'] ?? 0) === $id) {
                $order['status'] = $status;
            }
        }
        unset($order);
        saveOrders($orders);
        $notice = 'Order status updated.';
        $tab = 'orders';
    }

    $orders = getOrders();
    $shipping = getShippingSettings();
    $products = getProducts();
}

$filterStatus = $_GET['status'] ?? 'all';
$filteredOrders = $filterStatus === 'all' ? $orders : array_values(array_filter($orders, fn($o) => ($o['status'] ?? '') === $filterStatus));

$totalOrders = count($orders);
$deliveredOrders = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'delivered'));
$cancelledOrders = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'cancelled'));

$productCost = array_sum(array_map(fn($p) => (float) ($p['cost_bdt'] ?? 0), $products));
$otherCost = 25000 + 12000 + 18000 + 3500 + 2200;
$totalRevenue = array_sum(array_map(fn($o) => (float) ($o['grand_total_bdt'] ?? $o['amount_bdt'] ?? 0), $orders));
$estimatedPnl = $totalRevenue - ($productCost + $otherCost);

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <h2>Admin Dashboard</h2>
        <?php if ($notice): ?><p class="notice"><?php echo htmlspecialchars($notice); ?></p><?php endif; ?>

        <div class="admin-menu">
            <a class="<?php echo $tab === 'products' ? 'active' : ''; ?>" href="admin.php?tab=products">Products</a>
            <a class="<?php echo $tab === 'orders' ? 'active' : ''; ?>" href="admin.php?tab=orders">Orders</a>
            <a class="<?php echo $tab === 'pnl' ? 'active' : ''; ?>" href="admin.php?tab=pnl">PNL</a>
            <a class="<?php echo $tab === 'banner' ? 'active' : ''; ?>" href="admin.php?tab=banner">Banner</a>
            <a class="<?php echo $tab === 'users' ? 'active' : ''; ?>" href="admin.php?tab=users">Users</a>
            <a class="<?php echo $tab === 'shipping' ? 'active' : ''; ?>" href="admin.php?tab=shipping">Shipping</a>
        </div>

        <?php if ($tab === 'products'): ?>
            <div class="card admin-block">
                <h3>Add Product</h3>
                <form method="post">
                    <input type="hidden" name="action" value="add_product">
                    <div class="grid two-col">
                        <div class="form-group"><label>Title</label><input name="title" required></div>
                        <div class="form-group"><label>Image URL</label><input name="image" required></div>
                        <div class="form-group"><label>Price (BDT)</label><input name="price_bdt" type="number" step="0.01" required></div>
                        <div class="form-group"><label>Cost (BDT)</label><input name="cost_bdt" type="number" step="0.01" required></div>
                        <div class="form-group"><label>Colors (comma separated)</label><input name="colors"></div>
                        <div class="form-group"><label>Sizes (comma separated)</label><input name="sizes"></div>
                    </div>
                    <div class="form-group"><label>Short Description</label><textarea name="short_description" required></textarea></div>
                    <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
                    <button class="btn" type="submit">Add Product</button>
                </form>
            </div>

            <div class="card admin-block">
                <h3>Product List</h3>
                <?php foreach ($products as $product): ?>
                    <div class="admin-list-row">
                        <img src="<?php echo htmlspecialchars($product['images'][0] ?? ''); ?>" alt="product" class="admin-thumb">
                        <div>
                            <strong><?php echo htmlspecialchars($product['title']); ?></strong>
                            <p class="muted">Price: ৳<?php echo number_format((float) $product['price_bdt'], 0); ?> | Cost: ৳<?php echo number_format((float) $product['cost_bdt'], 0); ?></p>
                        </div>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>">
                            <button class="buy-btn" type="submit">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'orders'): ?>
            <div class="grid three-col">
                <article class="card"><h3>Total Orders</h3><p><?php echo $totalOrders; ?></p></article>
                <article class="card"><h3>Delivered</h3><p><?php echo $deliveredOrders; ?></p></article>
                <article class="card"><h3>Cancelled</h3><p><?php echo $cancelledOrders; ?></p></article>
            </div>
            <div class="card admin-block">
                <h3>Order List</h3>
                <div class="filter-buttons">
                    <a class="filter-btn <?php echo $filterStatus === 'all' ? 'active' : ''; ?>" href="admin.php?tab=orders&status=all">All</a>
                    <a class="filter-btn <?php echo $filterStatus === 'pending' ? 'active' : ''; ?>" href="admin.php?tab=orders&status=pending">Pending</a>
                    <a class="filter-btn <?php echo $filterStatus === 'confirmed' ? 'active' : ''; ?>" href="admin.php?tab=orders&status=confirmed">Confirmed</a>
                    <a class="filter-btn <?php echo $filterStatus === 'delivered' ? 'active' : ''; ?>" href="admin.php?tab=orders&status=delivered">Delivered</a>
                    <a class="filter-btn <?php echo $filterStatus === 'cancelled' ? 'active' : ''; ?>" href="admin.php?tab=orders&status=cancelled">Cancelled</a>
                </div>

                <div class="table-wrap">
                    <table class="order-table">
                        <thead>
                        <tr>
                            <th>SL</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Delivery</th>
                            <th>Total</th>
                            <th>Pickup</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $sl = 1; foreach ($filteredOrders as $order): ?>
                            <?php $items = is_array($order['items'] ?? null) ? $order['items'] : []; ?>
                            <?php if (!$items): $items = [[
                                'title' => $order['product_name'] ?? 'N/A',
                                'unit_price_bdt' => $order['amount_bdt'] ?? 0,
                                'qty' => 1,
                            ]]; endif; ?>
                            <?php foreach ($items as $idx => $item): ?>
                                <tr>
                                    <td><?php echo $sl++; ?></td>
                                    <td>#<?php echo (int) ($order['id'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? $order['customer'] ?? 'Customer'); ?></td>
                                    <td><?php echo htmlspecialchars($order['phone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($order['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($item['title'] ?? 'Item'); ?></td>
                                    <td>৳<?php echo number_format((float) ($item['unit_price_bdt'] ?? 0), 0); ?></td>
                                    <td><?php echo (int) ($item['qty'] ?? 1); ?></td>
                                    <td>৳<?php echo number_format((float) ($order['delivery_charge_bdt'] ?? 0), 0); ?></td>
                                    <td>৳<?php echo number_format((float) ($order['grand_total_bdt'] ?? $order['amount_bdt'] ?? 0), 0); ?></td>
                                    <td><button type="button" class="icon-btn small">Pickup Request</button></td>
                                    <td>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="action" value="update_order_status">
                                            <input type="hidden" name="id" value="<?php echo (int) ($order['id'] ?? 0); ?>">
                                            <select name="status">
                                                <?php foreach (['pending','confirmed','delivered','cancelled'] as $status): ?>
                                                    <option value="<?php echo $status; ?>" <?php echo (($order['status'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="icon-btn small" type="submit">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'pnl'): ?>
            <div class="grid two-col">
                <article class="card"><h3>Total Revenue</h3><p>৳<?php echo number_format($totalRevenue, 0); ?></p></article>
                <article class="card"><h3>Product Cost</h3><p>৳<?php echo number_format($productCost, 0); ?></p></article>
                <article class="card"><h3>Other Cost</h3><p>৳<?php echo number_format($otherCost, 0); ?></p></article>
                <article class="card"><h3>Estimated PNL</h3><p><?php echo $estimatedPnl >= 0 ? 'Profit' : 'Loss'; ?>: ৳<?php echo number_format(abs($estimatedPnl), 0); ?></p></article>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'banner'): ?>
            <div class="card admin-block">
                <h3>Banner & Branding</h3>
                <form method="post">
                    <input type="hidden" name="action" value="save_banner">
                    <div class="form-group"><label>Site Name</label><input type="text" name="site_name" value="<?php echo htmlspecialchars($siteConfig['site_name']); ?>"></div>
                    <div class="form-group"><label>Logo URL</label><input type="url" name="logo_url" value="<?php echo htmlspecialchars($siteConfig['logo_url']); ?>"></div>
                    <div class="form-group"><label>WhatsApp Number</label><input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($siteConfig['whatsapp_number']); ?>"></div>
                    <div class="form-group"><label>Facebook Pixel ID</label><input type="text" name="fb_pixel_id" value="<?php echo htmlspecialchars($siteConfig['fb_pixel_id'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Banner Heading</label><input type="text" name="banner_heading" value="<?php echo htmlspecialchars($siteConfig['banner_heading']); ?>"></div>
                    <div class="form-group"><label>Banner Subheading</label><textarea name="banner_subheading"><?php echo htmlspecialchars($siteConfig['banner_subheading']); ?></textarea></div>
                    <div class="form-group"><label>Banner Image URL</label><input type="url" name="banner_image" value="<?php echo htmlspecialchars($siteConfig['banner_image']); ?>"></div>
                    <button class="btn" type="submit">Save</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'users'): ?>
            <div class="card admin-block">
                <h3>Add User</h3>
                <form method="post">
                    <input type="hidden" name="action" value="add_user">
                    <div class="grid three-col">
                        <input name="name" placeholder="Name" required>
                        <input name="email" placeholder="Email" required>
                        <input name="role" placeholder="Role" required>
                    </div>
                    <button class="btn" type="submit">Add User</button>
                </form>
            </div>

            <div class="card admin-block">
                <h3>User List</h3>
                <?php foreach ($users as $user): ?>
                    <div class="admin-list-row">
                        <div>
                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                            <p class="muted"><?php echo htmlspecialchars($user['email']); ?> | <?php echo htmlspecialchars($user['role']); ?></p>
                        </div>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                            <button class="buy-btn" type="submit">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'shipping'): ?>
            <div class="card admin-block">
                <h3>Shipping Settings</h3>
                <form method="post">
                    <input type="hidden" name="action" value="save_shipping">
                    <div class="form-group"><label>Free Shipping Threshold (BDT)</label><input type="number" name="free_shipping_threshold_bdt" step="0.01" value="<?php echo (float) $shipping['free_shipping_threshold_bdt']; ?>"></div>
                    <div class="form-group"><label>Inside Dhaka Delivery Charge (BDT)</label><input type="number" name="inside_dhaka_charge_bdt" step="0.01" value="<?php echo (float) $shipping['inside_dhaka_charge_bdt']; ?>"></div>
                    <div class="form-group"><label>Outside Dhaka Delivery Charge (BDT)</label><input type="number" name="outside_dhaka_charge_bdt" step="0.01" value="<?php echo (float) $shipping['outside_dhaka_charge_bdt']; ?>"></div>
                    <button class="btn" type="submit">Save</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
