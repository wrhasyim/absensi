<?php
namespace App\Core;

class Router {
    private array $routes = [];

    public function get(string $path, $handler) { $this->add('GET', $path, $handler); }
    public function post(string $path, $handler) { $this->add('POST', $path, $handler); }
    public function any(string $path, $handler) {
        $this->add('GET', $path, $handler);
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, $handler) {
        $this->routes[] = ['method' => $method, 'path' => $path, 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri) {
        // strip base path
        $base = rtrim(BASE_PATH, '/');
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        if ($uri === '') $uri = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . rtrim($pattern, '/') . '/?$#';
            if (preg_match($pattern, rtrim($uri, '/') ?: '/', $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $m] = $handler;
                    $controller = new $class();
                    return call_user_func_array([$controller, $m], array_values($params));
                }
                return call_user_func_array($handler, array_values($params));
            }
        }
        http_response_code(404);
        $title = '404 — Tidak Ditemukan';
        $content = '<div class="card"><div class="card-body text-center py-5">'
                 . '<div style="font-size:64px;color:#94a3b8"><i class="bi bi-compass"></i></div>'
                 . '<h3 class="mt-3">Halaman Tidak Ditemukan</h3>'
                 . '<p class="text-muted">URL yang Anda cari tidak tersedia.</p>'
                 . '<a href="' . url('/dashboard') . '" class="btn btn-primary mt-2"><i class="bi bi-house"></i> Kembali ke Dashboard</a>'
                 . '</div></div>';
        if (\App\Core\Auth::check()) {
            include APP_ROOT . '/app/Views/layouts/app.php';
        } else {
            echo $content;
        }
    }
}
