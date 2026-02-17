<?php
if (!isset($pageTitle)) {
    $pageTitle = 'China Sourcing Hub';
}
if (!isset($siteConfig)) {
    require_once __DIR__ . '/config.php';
    $siteConfig = getSiteConfig();
}
$waNumber = preg_replace('/\D+/', '', $siteConfig['whatsapp_number'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWix+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkR4j8tbtH6i8l7kP6+0qL5Xx04h5v5w0Xbg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <?php if (!empty($siteConfig['fb_pixel_id'])): ?>
    <script>
      !function(f,b,e,v,n,t,s)
      {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};
      if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
      n.queue=[];t=b.createElement(e);t.async=!0;
      t.src=v;s=b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t,s)}(window, document,'script',
      'https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', '<?php echo htmlspecialchars($siteConfig['fb_pixel_id']); ?>');
      fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo htmlspecialchars($siteConfig['fb_pixel_id']); ?>&ev=PageView&noscript=1" /></noscript>
    <?php endif; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass ?? ''); ?>">
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php">
            <?php if (!empty($siteConfig['logo_url'])): ?>
                <img src="<?php echo htmlspecialchars($siteConfig['logo_url']); ?>" alt="<?php echo htmlspecialchars($siteConfig['site_name']); ?> logo" class="brand-logo">
            <?php endif; ?>
            <span><?php echo htmlspecialchars($siteConfig['site_name']); ?></span>
        </a>

        <button id="menu-toggle" class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-menu"><i class="fa-solid fa-bars"></i></button>

        <nav id="main-menu" class="menu">
            <a href="index.php">Home</a>
            <a href="index.php#services">Services</a>
            <a href="index.php#shop">Shop</a>
            <a href="admin.php">Admin</a>
            <?php if (!empty($waNumber)): ?>
                <a class="wa-btn" href="https://wa.me/<?php echo htmlspecialchars($waNumber); ?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
<script>
(() => {
    const toggle = document.getElementById('menu-toggle');
    const menu = document.getElementById('main-menu');
    if (!toggle || !menu) return;

    function setMenu(open) {
        menu.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', () => {
        setMenu(!menu.classList.contains('open'));
    });

    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 860) {
                setMenu(false);
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (window.innerWidth > 860) return;
        if (!menu.classList.contains('open')) return;
        if (!menu.contains(event.target) && event.target !== toggle) {
            setMenu(false);
        }
    });
})();
</script>
