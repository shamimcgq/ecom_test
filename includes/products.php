<?php

function defaultProducts(): array
{
    return [
        [
            'id' => 1,
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
        ],
    ];
}

function getProducts(): array
{
    $path = __DIR__ . '/../data/products.json';

    if (!file_exists($path)) {
        $seed = defaultProducts();
        for ($i = 2; $i <= 40; $i++) {
            $seed[] = [
                'id' => $i,
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
        return $seed;
    }

    $raw = file_get_contents($path);
    $products = json_decode($raw, true);
    return is_array($products) ? $products : defaultProducts();
}

function saveProducts(array $products): bool
{
    $path = __DIR__ . '/../data/products.json';
    return (bool) file_put_contents($path, json_encode(array_values($products), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function findProductById(int $id): ?array
{
    foreach (getProducts() as $product) {
        if ((int) ($product['id'] ?? 0) === $id) {
            return $product;
        }
    }

    return null;
}
