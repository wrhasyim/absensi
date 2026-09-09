<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Csrf;

class ParentController extends Controller {
    public function index() {
        Auth::require();
        $parents = Database::fetchAll("SELECT * FROM parents ORDER BY father_name LIMIT 200");
        $this->view('parents.index', compact('parents') + ['title' => 'Data Orang Tua']);
    }
    public function create() {
        Auth::require(['super_admin','admin']);
        $this->view('parents.form', ['parent' => null, 'title' => 'Tambah Orang Tua']);
    }
    public function store() {
        Auth::require(['super_admin','admin']); Csrf::verify();
        Database::insert('parents', $this->data());
        flash('success','Orang tua ditambahkan.');
        redirect('/parents');
    }
    public function edit($id) {
        Auth::require(['super_admin','admin']);
        $parent = Database::fetch("SELECT * FROM parents WHERE id=?", [$id]);
        $this->view('parents.form', compact('parent') + ['title' => 'Edit Orang Tua']);
    }
    public function update($id) {
        Auth::require(['super_admin','admin']); Csrf::verify();
        Database::update('parents', $this->data(), 'id=:id', ['id' => $id]);
        flash('success','Orang tua diupdate.');
        redirect('/parents');
    }
    public function delete($id) {
        Auth::require(['super_admin','admin']); Csrf::verify();
        Database::query("DELETE FROM parents WHERE id=?", [$id]);
        flash('success','Orang tua dihapus.');
        redirect('/parents');
    }
    private function data(): array {
        $ws = \App\Services\WhatsAppService::class;
        return [
            'father_name' => trim($this->input('father_name','')),
            'father_whatsapp' => $ws::normalizePhone($this->input('father_whatsapp','')),
            'mother_name' => trim($this->input('mother_name','')),
            'mother_whatsapp' => $ws::normalizePhone($this->input('mother_whatsapp','')),
            'guardian_name' => trim($this->input('guardian_name','')),
            'guardian_whatsapp' => $ws::normalizePhone($this->input('guardian_whatsapp','')),
            'primary_whatsapp' => $ws::normalizePhone($this->input('primary_whatsapp','')),
            'relation' => $this->input('relation','Orang Tua'),
            'wa_active' => $this->input('wa_active','1'),
            'is_active' => $this->input('is_active','1'),
        ];
    }
}
