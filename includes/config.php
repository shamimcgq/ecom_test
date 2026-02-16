<?php

function getSiteConfig(): array
{
    $defaultConfig = [
        'site_name' => 'China Sourcing Hub',
        'logo_url' => '',
        'whatsapp_number' => '8613800138000',
        'fb_pixel_id' => '',
        'banner_heading' => 'Source, Ship, and Scale with Confidence',
        'banner_subheading' => 'From wholesale sourcing to door-to-door delivery, we make China imports simple.',
        'banner_image' => 'https://images.unsplash.com/photo-1565891741441-64926e441838?auto=format&fit=crop&w=1500&q=80',
    ];

    $configPath = __DIR__ . '/../data/config.json';

    if (!file_exists($configPath)) {
        return $defaultConfig;
    }

    $json = file_get_contents($configPath);
    $config = json_decode($json, true);

    if (!is_array($config)) {
        return $defaultConfig;
    }

    return array_merge($defaultConfig, $config);
}

function saveSiteConfig(array $config): bool
{
    $configPath = __DIR__ . '/../data/config.json';
    return (bool) file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
