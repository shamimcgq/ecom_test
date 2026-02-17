<?php
require_once __DIR__ . '/includes/config.php';

$siteConfig = getSiteConfig();
$pageTitle = 'Inquiry Form | ' . $siteConfig['site_name'];

$service = isset($_GET['service']) ? trim($_GET['service']) : '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = trim($_POST['service'] ?? '');
    $message = 'Your query has been submitted. Thank you for contacting us.';
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <h2>Service Inquiry Form</h2>
        <?php if ($message): ?>
            <p class="notice"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="post" action="inquiry.php">
            <div class="form-group">
                <label for="service">Selected Service</label>
                <input type="text" id="service" name="service" value="<?php echo htmlspecialchars($service); ?>" required>
            </div>

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="product_detail">Product Detail with Link</label>
                <textarea id="product_detail" name="product_detail" placeholder="Describe the product and paste the product URL" required></textarea>
            </div>

            <div class="form-group">
                <label for="shipping_method">Shipping Method</label>
                <select id="shipping_method" name="shipping_method" required>
                    <option value="">Select shipping method</option>
                    <option value="Express (3-7 days)">Express (3-7 days)</option>
                    <option value="Standard (By Air 12-20 days)">Standard (By Air 12-20 days)</option>
                    <option value="By Sea (min 300kgs 30-40 days)">By Sea (min 300kgs 30-40 days)</option>
                </select>
            </div>

            <button type="submit" class="btn">Submit</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
