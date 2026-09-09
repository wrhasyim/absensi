<?php
// Simple .env loader
function load_env($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) ||
            (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        $_ENV[$k] = $v;
        putenv("$k=$v");
    }
}
load_env(__DIR__ . '/../.env');

// Composer autoload (untuk library seperti jmrashed/zkteco)
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) require_once $autoload;

function env($key, $default = null) {
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : $v;
}

define('APP_ROOT', dirname(__DIR__));
define('BASE_PATH', env('APP_BASE_PATH', ''));
define('APP_NAME', env('APP_NAME', 'Absensi Sekolah'));
define('STORAGE_PATH', APP_ROOT . '/storage');
define('LOG_PATH', STORAGE_PATH . '/logs');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Autoload simple PSR-4 style
spl_autoload_register(function ($class) {
    // App\Services\* lives in /services (outside /app)
    $svcPrefix = 'App\\Services\\';
    if (str_starts_with($class, $svcPrefix)) {
        $relative = substr($class, strlen($svcPrefix));
        $parts = explode('\\', $relative);
        $file = APP_ROOT . '/services/' . implode('/', $parts) . '.php';
        if (file_exists($file)) { require $file; return; }
        // fallback: lowercase sub-directories (e.g. Fingerprint -> fingerprint)
        $cls = array_pop($parts);
        $dirs = array_map('strtolower', $parts);
        $file = APP_ROOT . '/services/' . ($dirs ? implode('/', $dirs) . '/' : '') . $cls . '.php';
        if (file_exists($file)) { require $file; return; }
    }
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) require $file;
    }
});

// URL helper
function url($path = '') {
    $base = rtrim(BASE_PATH, '/');
    if ($path && !str_starts_with($path, '/')) $path = '/' . $path;
    return $base . $path;
}

function asset($path) {
    return url('/assets/' . ltrim($path, '/'));
}

function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

function old($key, $default = '') {
    return $_SESSION['_old'][$key] ?? $default;
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function flash($type, $msg = null) {
    if ($msg === null) {
        $m = $_SESSION['_flash'][$type] ?? null;
        unset($_SESSION['_flash'][$type]);
        return $m;
    }
    $_SESSION['_flash'][$type] = $msg;
}

function app_log($channel, $message) {
    $file = LOG_PATH . '/' . $channel . '.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
}
