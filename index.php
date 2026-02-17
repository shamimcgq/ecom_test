<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

if ($baseDir !== '' && $baseDir !== '.' && str_starts_with($path, $baseDir)) {
    $path = substr($path, strlen($baseDir)) ?: '/';
}

$path = '/' . ltrim($path, '/');

if ($path === '/' || $path === '/index.php') {
    require __DIR__ . '/home.php';
    exit;
}

if ($path === '/admin') {
    require __DIR__ . '/admin.php';
    exit;
}

if ($path === '/checkout') {
    require __DIR__ . '/checkout.php';
    exit;
}

if ($path === '/inquiry') {
    require __DIR__ . '/inquiry.php';
    exit;
}

if ($path === '/submit-order') {
    require __DIR__ . '/submit_order.php';
    exit;
}

if (preg_match('#^/product/([a-z0-9-]+)$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/product.php';
    exit;
}

http_response_code(404);
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>404</title></head>
<body><h1>404 - Page not found</h1></body></html>
