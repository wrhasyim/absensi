<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Csrf;

class AuthController extends Controller {
    public function showLogin() {
        if (Auth::check()) redirect('/dashboard');
        $this->view('auth.login', ['title' => 'Login'], 'auth');
    }

    public function login() {
        Csrf::verify();
        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');
        $user = Database::fetch("SELECT * FROM users WHERE username=? AND is_active=1", [$username]);
        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'Username atau password salah.');
            redirect('/login');
        }
        Auth::login($user);
        Auth::logActivity('login');
        redirect('/dashboard');
    }

    public function logout() {
        Auth::logActivity('logout');
        Auth::logout();
        redirect('/login');
    }
}
