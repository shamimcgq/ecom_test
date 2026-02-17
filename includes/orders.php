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

function nextOrderId(array $orders): int
{
    $maxId = 0;
    foreach ($orders as $order) {
        $id = (int) ($order['id'] ?? 0);
        if ($id > $maxId) {
            $maxId = $id;
        }
    }

    return $maxId + 1;
}

function addOrder(array $order): array
{
    $orders = getOrders();
    $order['id'] = nextOrderId($orders);
    $order['status'] = $order['status'] ?? 'pending';
    $order['created_at'] = date('c');
    $orders[] = $order;
    saveOrders($orders);
    return $order;
}
