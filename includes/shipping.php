<?php

function getShippingSettings(): array
{
    $default = [
        'free_shipping_threshold_bdt' => 1500,
        'inside_dhaka_charge_bdt' => 70,
        'outside_dhaka_charge_bdt' => 130,
    ];

    $path = __DIR__ . '/../data/shipping.json';
    if (!file_exists($path)) {
        file_put_contents($path, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $default;
    }

    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? array_merge($default, $data) : $default;
}

function saveShippingSettings(array $settings): bool
{
    $path = __DIR__ . '/../data/shipping.json';
    return (bool) file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
