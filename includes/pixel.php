<?php

require_once __DIR__ . '/config.php';

function sendServerSidePurchaseEvent(array $order, array $meta = []): void
{
    $cfg = getSiteConfig();
    $pixelId = trim((string) ($cfg['fb_pixel_id'] ?? ''));
    $token = trim((string) ($cfg['fb_pixel_token'] ?? ''));

    if ($pixelId === '' || $token === '') {
        return;
    }

    if (!function_exists('curl_init')) {
        return;
    }

    $eventId = (string) ($meta['event_id'] ?? ('order-' . ((string) ($order['id'] ?? time()))));
    $eventTime = time();
    $email = strtolower(trim((string) ($order['email'] ?? '')));
    $phone = preg_replace('/\D+/', '', (string) ($order['phone'] ?? ''));

    $payload = [
        'data' => [[
            'event_name' => 'Purchase',
            'event_time' => $eventTime,
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => (string) ($meta['event_source_url'] ?? ''),
            'user_data' => [
                'em' => $email !== '' ? [hash('sha256', $email)] : [],
                'ph' => $phone !== '' ? [hash('sha256', $phone)] : [],
                'client_ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                'client_user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'fbc' => (string) ($meta['fbc'] ?? ''),
                'fbp' => (string) ($meta['fbp'] ?? ''),
            ],
            'custom_data' => [
                'currency' => 'BDT',
                'value' => (float) ($order['grand_total_bdt'] ?? 0),
                'num_items' => count($order['items'] ?? []),
            ],
        ]],
    ];

    $url = sprintf('https://graph.facebook.com/v21.0/%s/events?access_token=%s', rawurlencode($pixelId), rawurlencode($token));

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);

    curl_exec($ch);
    curl_close($ch);
}
