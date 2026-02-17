<?php

require_once __DIR__ . '/db.php';

function getOrders(): array
{
    $pdo = db();
    $orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();

    if (!$orders) {
        return [];
    }

    $orderIds = array_map(fn($o) => (int) $o['id'], $orders);
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ({$placeholders}) ORDER BY id");
    $stmt->execute($orderIds);
    $itemRows = $stmt->fetchAll();

    $itemsByOrder = [];
    foreach ($itemRows as $item) {
        $orderId = (int) $item['order_id'];
        if (!isset($itemsByOrder[$orderId])) {
            $itemsByOrder[$orderId] = [];
        }

        $itemsByOrder[$orderId][] = [
            'id' => (int) $item['product_id'],
            'title' => (string) $item['title'],
            'qty' => (int) $item['qty'],
            'unit_price_bdt' => (float) $item['unit_price_bdt'],
            'line_total_bdt' => (float) $item['line_total_bdt'],
        ];
    }

    foreach ($orders as &$order) {
        $id = (int) $order['id'];
        $order['id'] = $id;
        $order['delivery_charge_bdt'] = (float) $order['delivery_charge_bdt'];
        $order['subtotal_bdt'] = (float) $order['subtotal_bdt'];
        $order['grand_total_bdt'] = (float) $order['grand_total_bdt'];
        $order['items'] = $itemsByOrder[$id] ?? [];
    }
    unset($order);

    return $orders;
}

function saveOrders(array $orders): bool
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $pdo->exec('DELETE FROM order_items');
        $pdo->exec('DELETE FROM orders');

        $orderStmt = $pdo->prepare(
            'INSERT INTO orders (id, customer_name, phone, address, email, size, color, notes, delivery_zone, delivery_charge_bdt, subtotal_bdt, grand_total_bdt, status, created_at)
             VALUES (:id, :customer_name, :phone, :address, :email, :size, :color, :notes, :delivery_zone, :delivery_charge_bdt, :subtotal_bdt, :grand_total_bdt, :status, :created_at)'
        );

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, title, qty, unit_price_bdt, line_total_bdt)
             VALUES (:order_id, :product_id, :title, :qty, :unit_price_bdt, :line_total_bdt)'
        );

        foreach ($orders as $order) {
            $orderStmt->execute([
                'id' => (int) ($order['id'] ?? 0),
                'customer_name' => (string) ($order['customer_name'] ?? $order['customer'] ?? 'Customer'),
                'phone' => (string) ($order['phone'] ?? ''),
                'address' => (string) ($order['address'] ?? ''),
                'email' => (string) ($order['email'] ?? ''),
                'size' => (string) ($order['size'] ?? ''),
                'color' => (string) ($order['color'] ?? ''),
                'notes' => (string) ($order['notes'] ?? ''),
                'delivery_zone' => (string) ($order['delivery_zone'] ?? 'inside_dhaka'),
                'delivery_charge_bdt' => (float) ($order['delivery_charge_bdt'] ?? 0),
                'subtotal_bdt' => (float) ($order['subtotal_bdt'] ?? 0),
                'grand_total_bdt' => (float) ($order['grand_total_bdt'] ?? $order['amount_bdt'] ?? 0),
                'status' => (string) ($order['status'] ?? 'pending'),
                'created_at' => (string) ($order['created_at'] ?? date('Y-m-d H:i:s')),
            ]);

            $orderId = (int) ($order['id'] ?? 0);
            $items = is_array($order['items'] ?? null) ? $order['items'] : [];
            foreach ($items as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => (int) ($item['id'] ?? 0),
                    'title' => (string) ($item['title'] ?? 'Item'),
                    'qty' => max(1, (int) ($item['qty'] ?? 1)),
                    'unit_price_bdt' => (float) ($item['unit_price_bdt'] ?? 0),
                    'line_total_bdt' => (float) ($item['line_total_bdt'] ?? 0),
                ]);
            }
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function addOrder(array $order): array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO orders (customer_name, phone, address, email, size, color, notes, delivery_zone, delivery_charge_bdt, subtotal_bdt, grand_total_bdt, status)
         VALUES (:customer_name, :phone, :address, :email, :size, :color, :notes, :delivery_zone, :delivery_charge_bdt, :subtotal_bdt, :grand_total_bdt, :status)'
    );

    $stmt->execute([
        'customer_name' => (string) ($order['customer_name'] ?? $order['customer'] ?? 'Customer'),
        'phone' => (string) ($order['phone'] ?? ''),
        'address' => (string) ($order['address'] ?? ''),
        'email' => (string) ($order['email'] ?? ''),
        'size' => (string) ($order['size'] ?? ''),
        'color' => (string) ($order['color'] ?? ''),
        'notes' => (string) ($order['notes'] ?? ''),
        'delivery_zone' => (string) ($order['delivery_zone'] ?? 'inside_dhaka'),
        'delivery_charge_bdt' => (float) ($order['delivery_charge_bdt'] ?? 0),
        'subtotal_bdt' => (float) ($order['subtotal_bdt'] ?? 0),
        'grand_total_bdt' => (float) ($order['grand_total_bdt'] ?? 0),
        'status' => (string) ($order['status'] ?? 'pending'),
    ]);

    $orderId = (int) $pdo->lastInsertId();
    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, title, qty, unit_price_bdt, line_total_bdt)
         VALUES (:order_id, :product_id, :title, :qty, :unit_price_bdt, :line_total_bdt)'
    );

    $items = is_array($order['items'] ?? null) ? $order['items'] : [];
    foreach ($items as $item) {
        $itemStmt->execute([
            'order_id' => $orderId,
            'product_id' => (int) ($item['id'] ?? 0),
            'title' => (string) ($item['title'] ?? 'Item'),
            'qty' => max(1, (int) ($item['qty'] ?? 1)),
            'unit_price_bdt' => (float) ($item['unit_price_bdt'] ?? 0),
            'line_total_bdt' => (float) ($item['line_total_bdt'] ?? 0),
        ]);
    }

    $order['id'] = $orderId;
    $order['created_at'] = date('c');
    return $order;
}

function updateOrderStatus(int $id, string $status): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
    return $stmt->execute(['status' => $status, 'id' => $id]);
}
