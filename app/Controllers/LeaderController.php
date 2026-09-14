<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Csrf;

class LeaderController extends Controller {
    public function index() {
        Auth::require();
        $leaders = Database::fetchAll("SELECT * FROM leaders ORDER BY kepsek_name");
        $this->view('leaders.index', compact('leaders') + ['title' => 'Data Pimpinan (Kepsek & Kurikulum)']);
    }

    public function create() {
        Auth::require(['super_admin', 'admin']);
        $this->view('leaders.form', ['title' => 'Tambah Pimpinan', 'l' => null]);
    }

    public function store() {
        Auth::require(['super_admin', 'admin']);
        Csrf::verify();
        Database::insert('leaders', $this->collectData());
        flash('success', 'Data Pimpinan berhasil ditambahkan.');
        redirect('/leaders');
    }

    public function edit($id) {
        Auth::require(['super_admin', 'admin']);
        $l = Database::fetch("SELECT * FROM leaders WHERE id=?", [$id]);
        $this->view('leaders.form', compact('l') + ['title' => 'Edit Pimpinan']);
    }

    public function update($id) {
        Auth::require(['super_admin', 'admin']);
        Csrf::verify();
        Database::update('leaders', $this->collectData(), 'id=:id', ['id' => $id]);
        flash('success', 'Data Pimpinan berhasil diupdate.');
        redirect('/leaders');
    }

    public function delete($id) {
        Auth::require(['super_admin', 'admin']);
        Csrf::verify();
        Database::query("DELETE FROM leaders WHERE id=?", [$id]);
        flash('success', 'Data dihapus.');
        redirect('/leaders');
    }

    private function collectData(): array {
        $formatNomor = function($nomor) {
            $nomor = trim($nomor);
            if (empty($nomor)) return '';
            $is_international = str_starts_with($nomor, '+');
            $clean = preg_replace('/[^0-9+]/', '', $nomor);
            if (empty($clean)) return '';
            if ($is_international) return $clean;
            if (str_starts_with($clean, '0')) return '62' . substr($clean, 1);
            if (str_starts_with($clean, '8') && strlen($clean) >= 9) return '62' . $clean;
            return str_replace('+', '', $clean);
        };

        return [
            'kepsek_name' => trim($this->input('kepsek_name', '')),
            'kepsek_wa' => $formatNomor($this->input('kepsek_wa', '')),
            'wakasek_name' => trim($this->input('wakasek_name', '')),
            'wakasek_wa' => $formatNomor($this->input('wakasek_wa', '')),
            'primary_whatsapp' => $formatNomor($this->input('primary_whatsapp', '')),
            'is_active' => $this->input('is_active', 1)
        ];
    }
}