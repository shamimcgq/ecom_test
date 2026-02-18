<?php

require_once __DIR__ . '/db.php';

function getSiteConfig(): array
{
    $defaultConfig = [
        'site_name' => 'China Sourcing Hub',
        'logo_url' => '',
        'whatsapp_number' => '8613800138000',
        'fb_pixel_id' => '',
        'fb_pixel_token' => '',
        'banner_heading' => 'Source, Ship, and Scale with Confidence',
        'banner_subheading' => 'From wholesale sourcing to door-to-door delivery, we make China imports simple.',
        'banner_image' => 'https://images.unsplash.com/photo-1565891741441-64926e441838?auto=format&fit=crop&w=1500&q=80',
    ];

    $pdo = db();
    $stmt = $pdo->query('SELECT config_key, config_value FROM site_config');
    $rows = $stmt->fetchAll();

    if (!$rows) {
        saveSiteConfig($defaultConfig);
        return $defaultConfig;
    }

    $dbConfig = [];
    foreach ($rows as $row) {
        $dbConfig[$row['config_key']] = $row['config_value'];
    }

    return array_merge($defaultConfig, $dbConfig);
}

function saveSiteConfig(array $config): bool
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO site_config (config_key, config_value) VALUES (:config_key, :config_value)
         ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)'
    );

    foreach ($config as $key => $value) {
        $stmt->execute([
            'config_key' => (string) $key,
            'config_value' => (string) $value,
        ]);
    }

    return true;
}
