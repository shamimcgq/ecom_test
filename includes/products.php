<?php

require_once __DIR__ . '/db.php';

function slugifyProductTitle(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'product';
}

function uniqueProductSlug(PDO $pdo, string $title, ?int $ignoreId = null): string
{
    $base = slugifyProductTitle($title);
    $slug = $base;
    $i = 2;

    while (true) {
        $sql = 'SELECT id FROM products WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }

        $stmt = $pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        if (!$stmt->fetch()) {
            return $slug;
        }

        $slug = $base . '-' . $i;
        $i++;
    }
}

function defaultProducts(): array
{
    return [[
        'id' => 1,
        'slug' => 'wireless-earbuds-pro',
        'title' => 'Wireless Earbuds Pro',
        'price_bdt' => 2200,
        'cost_bdt' => 1450,
        'short_description' => 'Low-latency earbuds with ANC and charging case.',
        'description_paragraphs' => [
            'These wireless earbuds are built for daily use, offering clear audio and stable Bluetooth connectivity for both music and calls.',
            'The compact charging case provides all-day battery backup and supports quick charging for busy users.',
            'A premium matte finish with ergonomic ear tips ensures comfort during long listening sessions.',
        ],
        'images' => [
            'https://picsum.photos/seed/earbud-main/800/500',
            'https://picsum.photos/seed/earbud-side/800/500',
            'https://picsum.photos/seed/earbud-case/800/500',
        ],
        'detail_images' => [
            ['url' => 'https://picsum.photos/seed/earbud-detail1/700/450', 'caption' => 'Noise-canceling dual microphones'],
            ['url' => 'https://picsum.photos/seed/earbud-detail2/700/450', 'caption' => 'Pocket-size magnetic charging case'],
        ],
        'variations' => [
            'colors' => ['Black', 'White', 'Navy'],
            'sizes' => ['Standard'],
        ],
    ]];
}

function seedProducts(): array
{
    $seed = defaultProducts();
    for ($i = 2; $i <= 40; $i++) {
        $seed[] = [
            'id' => $i,
            'slug' => "trending-product-{$i}",
            'title' => "Trending Product {$i}",
            'price_bdt' => 500 + ($i * 95),
            'cost_bdt' => 300 + ($i * 60),
            'short_description' => 'Quality checked sourcing item for retail and wholesale clients.',
            'description_paragraphs' => [
                'Reliable product sourced from trusted suppliers in China.',
                'Suitable for eCommerce, retail shelves, and gift packaging.',
                'Flexible MOQ available depending on sourcing plan.',
            ],
            'images' => [
                "https://picsum.photos/seed/china-product-{$i}/800/500",
                "https://picsum.photos/seed/china-product-{$i}-2/800/500",
            ],
            'detail_images' => [
                ['url' => "https://picsum.photos/seed/china-product-detail-{$i}/700/450", 'caption' => 'Close-up product quality shot'],
            ],
            'variations' => [
                'colors' => ['Red', 'Blue', 'Black'],
                'sizes' => ['S', 'M', 'L'],
            ],
        ];
    }

    saveProducts($seed);
    return getProducts();
}

function decodeJsonField($value, array $fallback = []): array
{
    if (is_array($value)) {
        return $value;
    }

    $decoded = json_decode((string) $value, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function normalizeProductRow(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'slug' => (string) ($row['slug'] ?? ''),
        'title' => (string) $row['title'],
        'price_bdt' => (float) $row['price_bdt'],
        'offer_price_bdt' => isset($row['offer_price_bdt']) ? (float) $row['offer_price_bdt'] : 0,
        'cost_bdt' => (float) $row['cost_bdt'],
        'short_description' => (string) ($row['short_description'] ?? ''),
        'description_paragraphs' => decodeJsonField($row['description_paragraphs'] ?? '[]'),
        'images' => decodeJsonField($row['images'] ?? '[]'),
        'detail_images' => decodeJsonField($row['detail_images'] ?? '[]'),
        'variations' => [
            'colors' => decodeJsonField($row['colors'] ?? '[]'),
            'sizes' => decodeJsonField($row['sizes'] ?? '[]'),
        ],
    ];
}

function getProducts(): array
{
    $pdo = db();
    $rows = $pdo->query('SELECT * FROM products ORDER BY id')->fetchAll();

    if (!$rows) {
        return seedProducts();
    }

    return array_map('normalizeProductRow', $rows);
}

function insertProductRow(PDO $pdo, array $product): void
{
    $title = (string) ($product['title'] ?? 'Product');
    $id = isset($product['id']) ? (int) $product['id'] : null;
    $slug = (string) ($product['slug'] ?? '');
    if ($slug === '') {
        $slug = uniqueProductSlug($pdo, $title, $id);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO products (id, slug, title, price_bdt, offer_price_bdt, cost_bdt, short_description, description_paragraphs, images, detail_images, colors, sizes)
         VALUES (:id, :slug, :title, :price_bdt, :offer_price_bdt, :cost_bdt, :short_description, :description_paragraphs, :images, :detail_images, :colors, :sizes)'
    );

    $stmt->execute([
        'id' => $id,
        'slug' => $slug,
        'title' => $title,
        'price_bdt' => (float) ($product['price_bdt'] ?? 0),
        'offer_price_bdt' => (float) ($product['offer_price_bdt'] ?? 0),
        'cost_bdt' => (float) ($product['cost_bdt'] ?? 0),
        'short_description' => (string) ($product['short_description'] ?? ''),
        'description_paragraphs' => json_encode(array_values($product['description_paragraphs'] ?? []), JSON_UNESCAPED_SLASHES),
        'images' => json_encode(array_values($product['images'] ?? []), JSON_UNESCAPED_SLASHES),
        'detail_images' => json_encode(array_values($product['detail_images'] ?? []), JSON_UNESCAPED_SLASHES),
        'colors' => json_encode(array_values($product['variations']['colors'] ?? []), JSON_UNESCAPED_SLASHES),
        'sizes' => json_encode(array_values($product['variations']['sizes'] ?? []), JSON_UNESCAPED_SLASHES),
    ]);
}

function addProduct(array $product): int
{
    $pdo = db();
    $title = (string) ($product['title'] ?? 'Product');
    $stmt = $pdo->prepare(
        'INSERT INTO products (slug, title, price_bdt, offer_price_bdt, cost_bdt, short_description, description_paragraphs, images, detail_images, colors, sizes)
         VALUES (:slug, :title, :price_bdt, :offer_price_bdt, :cost_bdt, :short_description, :description_paragraphs, :images, :detail_images, :colors, :sizes)'
    );

    $stmt->execute([
        'slug' => uniqueProductSlug($pdo, $title),
        'title' => $title,
        'price_bdt' => (float) ($product['price_bdt'] ?? 0),
        'offer_price_bdt' => (float) ($product['offer_price_bdt'] ?? 0),
        'cost_bdt' => (float) ($product['cost_bdt'] ?? 0),
        'short_description' => (string) ($product['short_description'] ?? ''),
        'description_paragraphs' => json_encode(array_values($product['description_paragraphs'] ?? []), JSON_UNESCAPED_SLASHES),
        'images' => json_encode(array_values($product['images'] ?? []), JSON_UNESCAPED_SLASHES),
        'detail_images' => json_encode(array_values($product['detail_images'] ?? []), JSON_UNESCAPED_SLASHES),
        'colors' => json_encode(array_values($product['variations']['colors'] ?? []), JSON_UNESCAPED_SLASHES),
        'sizes' => json_encode(array_values($product['variations']['sizes'] ?? []), JSON_UNESCAPED_SLASHES),
    ]);

    return (int) $pdo->lastInsertId();
}

function deleteProductById(int $id): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    return $stmt->execute(['id' => $id]);
}

function saveProducts(array $products): bool
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $pdo->exec('DELETE FROM products');
        foreach ($products as $product) {
            insertProductRow($pdo, $product);
        }
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function findProductById(int $id): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row ? normalizeProductRow($row) : null;
}

function findProductBySlug(string $slug): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $row = $stmt->fetch();

    return $row ? normalizeProductRow($row) : null;
}
