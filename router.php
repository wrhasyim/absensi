<?php
// Router script for PHP built-in server
// Serves static files directly, otherwise dispatches to public/index.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$base = '/api';

// Strip base
if (str_starts_with($uri, $base)) {
    $sub = substr($uri, strlen($base));
} else {
    $sub = $uri;
}

// Handle assets
if (preg_match('#^/assets/#', $sub) || preg_match('#^/uploads/#', $sub)) {
    $file = __DIR__ . '/public' . $sub;
    if (file_exists($file) && !is_dir($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimes = ['css'=>'text/css','js'=>'application/javascript','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','svg'=>'image/svg+xml','ico'=>'image/x-icon','woff'=>'font/woff','woff2'=>'font/woff2'];
        if (isset($mimes[$ext])) header('Content-Type: '.$mimes[$ext]);
        readfile($file);
        return true;
    }
}

require __DIR__ . '/public/index.php';
