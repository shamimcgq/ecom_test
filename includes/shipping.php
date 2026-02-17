<?php

require_once __DIR__ . '/db.php';

function getShippingSettings(): array
{
    $default = [
        'free_shipping_threshold_bdt' => 1500,
        'inside_dhaka_charge_bdt' => 70,
        'outside_dhaka_charge_bdt' => 130,
    ];

    $pdo = db();
    $stmt = $pdo->query('SELECT free_shipping_threshold_bdt, inside_dhaka_charge_bdt, outside_dhaka_charge_bdt FROM shipping_settings WHERE id = 1 LIMIT 1');
    $settings = $stmt->fetch();

    if (!$settings) {
        saveShippingSettings($default);
        return $default;
    }

    return array_merge($default, [
        'free_shipping_threshold_bdt' => (float) $settings['free_shipping_threshold_bdt'],
        'inside_dhaka_charge_bdt' => (float) $settings['inside_dhaka_charge_bdt'],
        'outside_dhaka_charge_bdt' => (float) $settings['outside_dhaka_charge_bdt'],
    ]);
}

function saveShippingSettings(array $settings): bool
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO shipping_settings (id, free_shipping_threshold_bdt, inside_dhaka_charge_bdt, outside_dhaka_charge_bdt)
         VALUES (1, :free_shipping_threshold_bdt, :inside_dhaka_charge_bdt, :outside_dhaka_charge_bdt)
         ON DUPLICATE KEY UPDATE
            free_shipping_threshold_bdt = VALUES(free_shipping_threshold_bdt),
            inside_dhaka_charge_bdt = VALUES(inside_dhaka_charge_bdt),
            outside_dhaka_charge_bdt = VALUES(outside_dhaka_charge_bdt)'
    );

    return $stmt->execute([
        'free_shipping_threshold_bdt' => (float) ($settings['free_shipping_threshold_bdt'] ?? 1500),
        'inside_dhaka_charge_bdt' => (float) ($settings['inside_dhaka_charge_bdt'] ?? 70),
        'outside_dhaka_charge_bdt' => (float) ($settings['outside_dhaka_charge_bdt'] ?? 130),
    ]);
}
