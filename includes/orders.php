<?php

function getOrders(): array
{
    $path = __DIR__ . '/../data/orders.json';
    if (!file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }

    $orders = json_decode((string) file_get_contents($path), true);
    return is_array($orders) ? $orders : [];
}

function saveOrders(array $orders): bool
{
    $path = __DIR__ . '/../data/orders.json';
    return (bool) file_put_contents($path, json_encode(array_values($orders), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function addOrder(array $order): array
{
    $orders = getOrders();
    $nextId = empty($orders) ? 1 : (max(array_column($orders, 'id')) + 1);
    $order['id'] = $nextId;
    $order['status'] = $order['status'] ?? 'pending';
    $order['created_at'] = date('c');
    $orders[] = $order;
    saveOrders($orders);
    return $order;
}
