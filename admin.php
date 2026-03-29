<?php
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/shipping.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/users.php';

$siteConfig = getSiteConfig();
$pageTitle = 'Admin Panel | ' . $siteConfig['site_name'];
$notice = '';
$tab = $_GET['tab'] ?? 'orders';

function uploadProductImages(string $inputName): array
{
    if (empty($_FILES[$inputName]) || !isset($_FILES[$inputName]['tmp_name'])) {
        return [];
    }

    $dir = __DIR__ . '/data/productsimgs';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $uploaded = [];
    $tmpNames = (array) $_FILES[$inputName]['tmp_name'];
    $originalNames = (array) ($_FILES[$inputName]['name'] ?? []);

    foreach ($tmpNames as $index => $tmpName) {
        if (!$tmpName || !is_uploaded_file($tmpName)) {
            continue;
        }

        $ext = strtolower(pathinfo((string) ($originalNames[$index] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            continue;
        }

        $name = 'product-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $name;
        if (move_uploaded_file($tmpName, $dest)) {
            $uploaded[] = 'data/productsimgs/' . $name;
        }
    }

    return $uploaded;
}

function buildDetailImages(array $uploadedDetailImages, array $captions): array
{
    $detailImages = [];
    foreach ($uploadedDetailImages as $index => $url) {
        $caption = trim((string) ($captions[$index] ?? ''));
        $detailImages[] = [
            'url' => $url,
            'caption' => $caption !== '' ? $caption : 'Product detail',
        ];
    }

    return $detailImages;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $user = authenticateAdmin(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''));
    if ($user) {
        $_SESSION['admin_user'] = [
            'username' => $user['username'],
            'role' => $user['role'],
            'name' => $user['name'] ?? $user['username'],
        ];
        header('Location: admin.php');
        exit;
    }

    $notice = 'Invalid username or password.';
}

$adminUser = $_SESSION['admin_user'] ?? null;
if (!$adminUser) {
    require_once __DIR__ . '/includes/header.php';
    ?>
    <section class="section">
        <div class="container" style="max-width:520px;">
            <article class="card">
                <h2>Admin Login</h2>
                <?php if ($notice): ?><p class="notice"><?php echo htmlspecialchars($notice); ?></p><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group"><label>Username</label><input name="username" required></div>
                    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                    <button class="btn" type="submit">Login</button>
                </form>
                <p class="muted">Sample users: superadmin / Admin@123, manager / Manager@123, ops / Ops@123</p>
            </article>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$allowedTabs = roleTabs((string) $adminUser['role']);
if (!in_array($tab, $allowedTabs, true)) {
    $tab = $allowedTabs[0] ?? 'orders';
}

$products = getProducts();
$orders = getOrders();
$users = getUsers();
$shipping = getShippingSettings();

$canManageProducts = in_array('products', $allowedTabs, true);
$canManageOrders = in_array('orders', $allowedTabs, true);
$canManageShipping = in_array('shipping', $allowedTabs, true);
$canManageUsers = in_array('users', $allowedTabs, true);
$canManageBanner = in_array('banner', $allowedTabs, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'login') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_banner' && $canManageBanner) {
        $siteConfig['site_name'] = trim($_POST['site_name'] ?? $siteConfig['site_name']);
        $siteConfig['logo_url'] = trim($_POST['logo_url'] ?? $siteConfig['logo_url']);
        $siteConfig['whatsapp_number'] = trim($_POST['whatsapp_number'] ?? $siteConfig['whatsapp_number']);
        $siteConfig['fb_pixel_id'] = trim($_POST['fb_pixel_id'] ?? $siteConfig['fb_pixel_id']);
        $siteConfig['fb_pixel_token'] = trim($_POST['fb_pixel_token'] ?? $siteConfig['fb_pixel_token']);
        $siteConfig['banner_heading'] = trim($_POST['banner_heading'] ?? $siteConfig['banner_heading']);
        $siteConfig['banner_subheading'] = trim($_POST['banner_subheading'] ?? $siteConfig['banner_subheading']);
        $siteConfig['banner_image'] = trim($_POST['banner_image'] ?? $siteConfig['banner_image']);
        saveSiteConfig($siteConfig);
        $notice = 'Branding settings updated.';
        $tab = 'banner';
    }

    if ($action === 'add_product' && $canManageProducts) {
        $uploadedPrimaryImages = uploadProductImages('product_images');
        $uploadedDetailImages = uploadProductImages('detail_images');
        $detailCaptions = (array) ($_POST['detail_captions'] ?? []);
        $detailImages = buildDetailImages($uploadedDetailImages, $detailCaptions);
        $primaryImages = $uploadedPrimaryImages ?: ['https://picsum.photos/seed/new-product/800/500'];
        if (!$detailImages) {
            $detailImages = [[
                'url' => $primaryImages[0],
                'caption' => 'Primary view',
            ]];
        }

        $newProduct = [
            'title' => trim($_POST['title'] ?? 'New Product'),
            'price_bdt' => (float) ($_POST['price_bdt'] ?? 0),
            'offer_price_bdt' => (float) ($_POST['offer_price_bdt'] ?? 0),
            'cost_bdt' => (float) ($_POST['cost_bdt'] ?? 0),
            'stock_qty' => (int) ($_POST['stock_qty'] ?? 0),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'description_paragraphs' => [trim($_POST['description'] ?? '')],
            'images' => $primaryImages,
            'detail_images' => $detailImages,
            'variations' => [
                'colors' => array_values(array_filter(array_map('trim', explode(',', (string) ($_POST['colors'] ?? ''))))),
                'sizes' => array_values(array_filter(array_map('trim', explode(',', (string) ($_POST['sizes'] ?? ''))))),
            ],
        ];
        addProduct($newProduct);
        $notice = 'Product added.';
        $tab = 'products';
    }

    if ($action === 'edit_product' && $canManageProducts) {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = findProductById($id);
        if ($existing) {
            $uploadedPrimaryImages = uploadProductImages('product_images');
            $images = $uploadedPrimaryImages ?: (array) ($existing['images'] ?? []);
            if (!$images) {
                $images = ['https://picsum.photos/seed/new-product/800/500'];
            }

            $existingDetailUrls = (array) ($_POST['existing_detail_urls'] ?? []);
            $existingDetailCaptions = (array) ($_POST['existing_detail_captions'] ?? []);
            $keptDetailImages = [];
            foreach ($existingDetailUrls as $index => $url) {
                $cleanUrl = trim((string) $url);
                if ($cleanUrl === '') {
                    continue;
                }
                $keptDetailImages[] = [
                    'url' => $cleanUrl,
                    'caption' => trim((string) ($existingDetailCaptions[$index] ?? '')) ?: 'Product detail',
                ];
            }

            $uploadedDetailImages = uploadProductImages('detail_images');
            $detailCaptions = (array) ($_POST['detail_captions'] ?? []);
            $newDetailImages = buildDetailImages($uploadedDetailImages, $detailCaptions);
            $detailImages = $newDetailImages ?: ($keptDetailImages ?: (array) ($existing['detail_images'] ?? []));
            if (!$detailImages) {
                $detailImages = [['url' => $images[0], 'caption' => 'Primary view']];
            }

            updateProduct($id, [
                'title' => trim($_POST['title'] ?? $existing['title']),
                'price_bdt' => (float) ($_POST['price_bdt'] ?? $existing['price_bdt']),
                'offer_price_bdt' => (float) ($_POST['offer_price_bdt'] ?? ($existing['offer_price_bdt'] ?? 0)),
                'cost_bdt' => (float) ($_POST['cost_bdt'] ?? $existing['cost_bdt']),
                'stock_qty' => (int) ($_POST['stock_qty'] ?? ($existing['stock_qty'] ?? 0)),
                'short_description' => trim($_POST['short_description'] ?? $existing['short_description']),
                'description_paragraphs' => [trim($_POST['description'] ?? (($existing['description_paragraphs'][0] ?? '')))],
                'images' => $images,
                'detail_images' => $detailImages,
                'variations' => [
                    'colors' => array_values(array_filter(array_map('trim', explode(',', (string) ($_POST['colors'] ?? implode(', ', $existing['variations']['colors'] ?? [])))))),
                    'sizes' => array_values(array_filter(array_map('trim', explode(',', (string) ($_POST['sizes'] ?? implode(', ', $existing['variations']['sizes'] ?? [])))))),
                ],
            ]);
            $notice = 'Product updated.';
        }
        $tab = 'products';
    }

    if ($action === 'delete_product' && $canManageProducts) {
        $id = (int) ($_POST['id'] ?? 0);
        deleteProductById($id);
        $notice = 'Product deleted.';
        $tab = 'products';
    }

    if ($action === 'add_user' && $canManageUsers) {
        addUser([
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => trim($_POST['role'] ?? 'customer'),
        ]);
        $notice = 'User added.';
        $tab = 'users';
    }

    if ($action === 'delete_user' && $canManageUsers) {
        $id = (int) ($_POST['id'] ?? 0);
        deleteUserById($id);
        $notice = 'User deleted.';
        $tab = 'users';
    }

    if ($action === 'save_shipping' && $canManageShipping) {
        $shipping['free_shipping_threshold_bdt'] = (float) ($_POST['free_shipping_threshold_bdt'] ?? $shipping['free_shipping_threshold_bdt']);
        $shipping['inside_dhaka_charge_bdt'] = (float) ($_POST['inside_dhaka_charge_bdt'] ?? $shipping['inside_dhaka_charge_bdt']);
        $shipping['outside_dhaka_charge_bdt'] = (float) ($_POST['outside_dhaka_charge_bdt'] ?? $shipping['outside_dhaka_charge_bdt']);
        saveShippingSettings($shipping);
        $notice = 'Shipping settings updated.';
        $tab = 'shipping';
    }

    if ($action === 'update_order_status' && $canManageOrders) {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'pending');
        updateOrderStatus($id, $status);
        $notice = 'Order status updated.';
        $tab = 'orders';
    }

    $orders = getOrders();
    $shipping = getShippingSettings();
    $products = getProducts();
    $users = getUsers();
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
        <div class="admin-head">
            <h2>Admin Dashboard</h2>
            <p class="muted">Signed in as <?php echo htmlspecialchars($adminUser['name'] . ' (' . $adminUser['role'] . ')'); ?> | <a href="admin.php?logout=1">Logout</a></p>
        </div>
        <?php if ($notice): ?><p class="notice"><?php echo htmlspecialchars($notice); ?></p><?php endif; ?>

        <div class="admin-menu">
            <?php foreach ($allowedTabs as $allowedTab): ?>
                <a class="<?php echo $tab === $allowedTab ? 'active' : ''; ?>" href="admin.php?tab=<?php echo urlencode($allowedTab); ?>"><?php echo ucfirst($allowedTab); ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($tab === 'products' && $canManageProducts): ?>
            <div class="card admin-block">
                <h3>Add Product</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">
                    <div class="grid two-col">
                        <div class="form-group"><label>Title</label><input name="title" required></div>
                        <div class="form-group"><label>Primary Product Images</label><input name="product_images[]" type="file" accept="image/*" multiple></div>
                        <div class="form-group"><label>Detailed Product Images</label><input name="detail_images[]" type="file" accept="image/*" multiple></div>
                        <div class="form-group"><label>Price (BDT)</label><input name="price_bdt" type="number" step="0.01" required></div>
                        <div class="form-group"><label>Offer Price (BDT)</label><input name="offer_price_bdt" type="number" step="0.01"></div>
                        <div class="form-group"><label>Cost (BDT)</label><input name="cost_bdt" type="number" step="0.01" required></div>
                        <div class="form-group"><label>Stock Qty</label><input name="stock_qty" type="number" step="1" min="0" value="0" required></div>
                        <div class="form-group"><label>Colors (comma separated)</label><input name="colors"></div>
                        <div class="form-group"><label>Sizes (comma separated)</label><input name="sizes"></div>
                    </div>
                    <div class="form-group"><label>Short Description</label><textarea name="short_description" required></textarea></div>
                    <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
                    <div class="grid three-col">
                        <div class="form-group"><label>Detail Caption 1</label><input name="detail_captions[]" placeholder="Close-up view"></div>
                        <div class="form-group"><label>Detail Caption 2</label><input name="detail_captions[]" placeholder="Material quality"></div>
                        <div class="form-group"><label>Detail Caption 3</label><input name="detail_captions[]" placeholder="Packaging / usage"></div>
                    </div>
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
                            <p class="muted">Price: ৳<?php echo number_format((float) $product['price_bdt'], 0); ?> | Offer: ৳<?php echo number_format((float) ($product['offer_price_bdt'] ?? 0), 0); ?> | Cost: ৳<?php echo number_format((float) $product['cost_bdt'], 0); ?></p>
                            <p class="muted">Stock: <?php echo (int) ($product['stock_qty'] ?? 0); ?></p>
                            <details>
                                <summary>Edit Product</summary>
                                <form method="post" enctype="multipart/form-data" class="card">
                                    <input type="hidden" name="action" value="edit_product">
                                    <input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>">
                                    <div class="grid two-col">
                                        <div class="form-group"><label>Title</label><input name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required></div>
                                        <div class="form-group"><label>Primary Product Images</label><input name="product_images[]" type="file" accept="image/*" multiple></div>
                                        <div class="form-group"><label>Add New Detail Images</label><input name="detail_images[]" type="file" accept="image/*" multiple></div>
                                        <div class="form-group"><label>Price</label><input name="price_bdt" type="number" step="0.01" value="<?php echo (float) $product['price_bdt']; ?>" required></div>
                                        <div class="form-group"><label>Offer Price</label><input name="offer_price_bdt" type="number" step="0.01" value="<?php echo (float) ($product['offer_price_bdt'] ?? 0); ?>"></div>
                                        <div class="form-group"><label>Cost</label><input name="cost_bdt" type="number" step="0.01" value="<?php echo (float) $product['cost_bdt']; ?>" required></div>
                                        <div class="form-group"><label>Stock Qty</label><input name="stock_qty" type="number" min="0" value="<?php echo (int) ($product['stock_qty'] ?? 0); ?>" required></div>
                                        <div class="form-group"><label>Colors</label><input name="colors" value="<?php echo htmlspecialchars(implode(', ', $product['variations']['colors'] ?? [])); ?>"></div>
                                        <div class="form-group"><label>Sizes</label><input name="sizes" value="<?php echo htmlspecialchars(implode(', ', $product['variations']['sizes'] ?? [])); ?>"></div>
                                    </div>
                                    <div class="form-group"><label>Short Description</label><textarea name="short_description" required><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea></div>
                                    <div class="form-group"><label>Description</label><textarea name="description"><?php echo htmlspecialchars($product['description_paragraphs'][0] ?? ''); ?></textarea></div>
                                    <?php if (!empty($product['detail_images']) && is_array($product['detail_images'])): ?>
                                        <div class="grid two-col">
                                            <?php foreach ($product['detail_images'] as $detail): ?>
                                                <div class="form-group">
                                                    <label>Existing Detail Caption</label>
                                                    <input type="hidden" name="existing_detail_urls[]" value="<?php echo htmlspecialchars((string) ($detail['url'] ?? '')); ?>">
                                                    <input name="existing_detail_captions[]" value="<?php echo htmlspecialchars((string) ($detail['caption'] ?? '')); ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="grid three-col">
                                        <div class="form-group"><label>New Detail Caption 1</label><input name="detail_captions[]"></div>
                                        <div class="form-group"><label>New Detail Caption 2</label><input name="detail_captions[]"></div>
                                        <div class="form-group"><label>New Detail Caption 3</label><input name="detail_captions[]"></div>
                                    </div>
                                    <button class="btn" type="submit">Save Changes</button>
                                </form>
                            </details>
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

        <?php if ($tab === 'orders' && $canManageOrders): ?>
            <div class="grid three-col">
                <article class="card"><h3>Total Orders</h3><p><?php echo $totalOrders; ?></p></article>
                <article class="card"><h3>Delivered</h3><p><?php echo $deliveredOrders; ?></p></article>
                <article class="card"><h3>Cancelled</h3><p><?php echo $cancelledOrders; ?></p></article>
            </div>
            <div class="card admin-block">
                <h3>Order List</h3>
                <div class="filter-buttons">
                    <?php foreach (['all','pending','confirmed','delivered','cancelled','returned'] as $status): ?>
                        <a class="filter-btn <?php echo $filterStatus === $status ? 'active' : ''; ?>" href="admin.php?tab=orders&status=<?php echo $status; ?>"><?php echo ucfirst($status); ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="table-wrap">
                    <table class="order-table">
                        <thead>
                        <tr>
                            <th>SL</th><th>Order ID</th><th>Customer</th><th>Phone</th><th>Email</th><th>Product</th><th>Price</th><th>Qty</th><th>Delivery</th><th>Total</th><th>Pickup</th><th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $sl = 1; foreach ($filteredOrders as $order): ?>
                            <?php $items = is_array($order['items'] ?? null) ? $order['items'] : []; ?>
                            <?php if (!$items): $items = [['title' => $order['product_name'] ?? 'N/A','unit_price_bdt' => $order['amount_bdt'] ?? 0,'qty' => 1]]; endif; ?>
                                <tr>
                                    <td><?php echo $sl++; ?></td>
                                    <td>#<?php echo (int) ($order['id'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? $order['customer'] ?? 'Customer'); ?></td>
                                    <td><?php echo htmlspecialchars($order['phone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($order['email'] ?? '-'); ?></td>
                                    <td>
                                        <ul class="order-products-list">
                                            <?php foreach ($items as $item): ?>
                                                <li><?php echo htmlspecialchars($item['title'] ?? 'Item'); ?> (x<?php echo (int) ($item['qty'] ?? 1); ?>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td>৳<?php echo number_format((float) ($order['subtotal_bdt'] ?? $order['amount_bdt'] ?? 0), 0); ?></td>
                                    <td>-</td>
                                    <td>৳<?php echo number_format((float) ($order['delivery_charge_bdt'] ?? 0), 0); ?></td>
                                    <td>৳<?php echo number_format((float) ($order['grand_total_bdt'] ?? $order['amount_bdt'] ?? 0), 0); ?></td>
                                    <td><button type="button" class="icon-btn small">Pickup Request</button></td>
                                    <td>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="action" value="update_order_status">
                                            <input type="hidden" name="id" value="<?php echo (int) ($order['id'] ?? 0); ?>">
                                            <select name="status">
                                                <?php foreach (['pending','confirmed','delivered','cancelled','returned'] as $status): ?>
                                                    <option value="<?php echo $status; ?>" <?php echo (($order['status'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="icon-btn small" type="submit">Save</button>
                                        </form>
                                    </td>
                                </tr>
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

        <?php if ($tab === 'banner' && $canManageBanner): ?>
            <div class="card admin-block">
                <h3>Banner & Branding</h3>
                <form method="post">
                    <input type="hidden" name="action" value="save_banner">
                    <div class="form-group"><label>Site Name</label><input type="text" name="site_name" value="<?php echo htmlspecialchars($siteConfig['site_name']); ?>"></div>
                    <div class="form-group"><label>Logo URL</label><input type="url" name="logo_url" value="<?php echo htmlspecialchars($siteConfig['logo_url']); ?>"></div>
                    <div class="form-group"><label>WhatsApp Number</label><input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($siteConfig['whatsapp_number']); ?>"></div>
                    <div class="form-group"><label>Facebook Pixel ID</label><input type="text" name="fb_pixel_id" value="<?php echo htmlspecialchars($siteConfig['fb_pixel_id'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Facebook Pixel API Token</label><input type="text" name="fb_pixel_token" value="<?php echo htmlspecialchars($siteConfig['fb_pixel_token'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Banner Heading</label><input type="text" name="banner_heading" value="<?php echo htmlspecialchars($siteConfig['banner_heading']); ?>"></div>
                    <div class="form-group"><label>Banner Subheading</label><textarea name="banner_subheading"><?php echo htmlspecialchars($siteConfig['banner_subheading']); ?></textarea></div>
                    <div class="form-group"><label>Banner Image URL</label><input type="url" name="banner_image" value="<?php echo htmlspecialchars($siteConfig['banner_image']); ?>"></div>
                    <button class="btn" type="submit">Save</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'users' && $canManageUsers): ?>
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

        <?php if ($tab === 'shipping' && $canManageShipping): ?>
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
