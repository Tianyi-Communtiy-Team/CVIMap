<?php
require_once __DIR__ . '/common.php';

$val = is_file(__DIR__ . '/core_assets/core_favicon.png')
    ? 'core_assets/core_favicon.png'
    : getConfig('favicon', '');

if ($val === '') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'favicon not set';
    exit;
}

if (preg_match('#^https?://#i', $val)) {
    header('Location: ' . $val);
    exit;
}

if (strpos($val, '..') !== false) {
    http_response_code(404);
    exit;
}

$path = __DIR__ . '/' . ltrim($val, '/');

if (!is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'favicon file not found: ' . $val;
    exit;
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = [
    'ico' => 'image/x-icon',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'webp' => 'image/webp',
];

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Cache-Control: no-cache, must-revalidate');
readfile($path);
