<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/products.php';

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

function addOrder(array $order): array
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
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
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $productId = (int) ($item['id'] ?? 0);

            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $productId,
                'title' => (string) ($item['title'] ?? 'Item'),
                'qty' => $qty,
                'unit_price_bdt' => (float) ($item['unit_price_bdt'] ?? 0),
                'line_total_bdt' => (float) ($item['line_total_bdt'] ?? 0),
            ]);

            if ($productId > 0) {
                adjustProductStock($productId, -$qty);
            }
        }

        $pdo->commit();
        $order['id'] = $orderId;
        $order['created_at'] = date('c');
        return $order;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getOrderById(int $id): ?array
{
    foreach (getOrders() as $order) {
        if ((int) ($order['id'] ?? 0) === $id) {
            return $order;
        }
    }

    return null;
}

function updateOrderStatus(int $id, string $status): bool
{
    $status = strtolower(trim($status));
    $allowed = ['pending', 'confirmed', 'delivered', 'cancelled', 'returned'];
    if (!in_array($status, $allowed, true)) {
        $status = 'pending';
    }

    $order = getOrderById($id);
    if (!$order) {
        return false;
    }

    $current = (string) ($order['status'] ?? 'pending');
    if ($current === $status) {
        return true;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($status === 'returned' && $current !== 'returned') {
            foreach ($order['items'] as $item) {
                adjustProductStock((int) ($item['id'] ?? 0), (int) ($item['qty'] ?? 0));
            }
        }

        if ($current === 'returned' && $status !== 'returned') {
            foreach ($order['items'] as $item) {
                adjustProductStock((int) ($item['id'] ?? 0), -((int) ($item['qty'] ?? 0)));
            }
        }

        $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $ok = $stmt->execute(['status' => $status, 'id' => $id]);
        $pdo->commit();
        return $ok;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
