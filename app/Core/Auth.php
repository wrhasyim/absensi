<?php
namespace App\Core;

class Auth {
    public static function user() {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool {
        return isset($_SESSION['user']);
    }

    public static function login(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role'],
            'email' => $user['email'] ?? null,
        ];
    }

    public static function logout(): void {
        $_SESSION = [];
        session_destroy();
    }

    public static function role(): ?string {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function hasRole(...$roles): bool {
        return in_array(self::role(), $roles, true);
    }

    public static function require(?array $roles = null): void {
        if (!self::check()) {
            flash('error', 'Silakan login terlebih dahulu.');
            redirect('/login');
        }
        if ($roles && !in_array(self::role(), $roles, true)) {
            http_response_code(403);
            $title = '403 — Akses Ditolak';
            $content = '<div class="card"><div class="card-body text-center py-5">'
                     . '<div style="font-size:64px;color:#f87171"><i class="bi bi-shield-lock-fill"></i></div>'
                     . '<h3 class="mt-3">Akses Ditolak</h3>'
                     . '<p class="text-muted">Role Anda (<b>' . htmlspecialchars(self::role() ?? '-') . '</b>) tidak diizinkan mengakses halaman ini.</p>'
                     . '<a href="' . url('/dashboard') . '" class="btn btn-primary mt-2"><i class="bi bi-house"></i> Kembali ke Dashboard</a>'
                     . '</div></div>';
            include APP_ROOT . '/app/Views/layouts/app.php';
            exit;
        }
    }

    public static function logActivity(string $activity, string $detail = ''): void {
        $userId = self::user()['id'] ?? null;
        Database::insert('activity_logs', [
            'user_id' => $userId,
            'activity' => $activity,
            'detail' => $detail,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
