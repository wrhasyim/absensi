<?php
namespace App\Core;

class Controller {
    protected function view(string $view, array $data = [], string $layout = 'app') {
        extract($data);
        $viewFile = APP_ROOT . '/app/Views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            die("View not found: $view");
        }
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        if ($layout) {
            $layoutFile = APP_ROOT . "/app/Views/layouts/{$layout}.php";
            include $layoutFile;
        } else {
            echo $content;
        }
    }

    protected function json($data, int $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function input(string $key, $default = null) {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}
