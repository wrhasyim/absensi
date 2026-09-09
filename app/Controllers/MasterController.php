<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Csrf;

class MasterController extends Controller {
    // ===== Majors =====
    public function majorsIndex() {
        Auth::require();
        $majors = Database::fetchAll("SELECT * FROM majors ORDER BY name");
        $this->view('majors.index', compact('majors') + ['title'=>'Data Jurusan']);
    }
    public function majorStore() {
        Auth::require(['super_admin','admin']); Csrf::verify();
        $id = $this->input('id');
        $data = ['code'=>trim($this->input('code','')),'name'=>trim($this->input('name','')),'description'=>trim($this->input('description',''))];
        if ($id) Database::update('majors',$data,'id=:id',['id'=>$id]);
        else Database::insert('majors',$data);
        flash('success','Jurusan disimpan.');
        redirect('/majors');
    }
    public function majorDelete($id) {
        Auth::require(['super_admin','admin']); Csrf::verify();
        Database::query("DELETE FROM majors WHERE id=?", [$id]);
        flash('success','Jurusan dihapus.'); redirect('/majors');
    }

    // ===== Classes =====
    public function classesIndex() {
        Auth::require();
        $classes = Database::fetchAll("SELECT c.*, m.name major_name FROM classes c LEFT JOIN majors m ON m.id=c.major_id ORDER BY c.name");
        $majors = Database::fetchAll("SELECT * FROM majors ORDER BY name");
        $this->view('classes.index', compact('classes','majors') + ['title'=>'Data Kelas']);
    }
    public function classStore() {
        Auth::require(['super_admin','admin']); Csrf::verify();
        $id = $this->input('id');
        $data = ['name'=>trim($this->input('name','')),'grade'=>trim($this->input('grade','')),'major_id'=>$this->input('major_id') ?: null];
        if ($id) Database::update('classes',$data,'id=:id',['id'=>$id]);
        else Database::insert('classes',$data);
        flash('success','Kelas disimpan.'); redirect('/classes');
    }
    public function classDelete($id) {
        Auth::require(['super_admin','admin']); Csrf::verify();
        Database::query("DELETE FROM classes WHERE id=?", [$id]);
        flash('success','Kelas dihapus.'); redirect('/classes');
    }

    // ===== Teachers =====
    public function teachersIndex() {
        Auth::require();
        $teachers = Database::fetchAll("SELECT * FROM teachers ORDER BY name");
        $this->view('teachers.index', compact('teachers') + ['title'=>'Data Guru']);
    }
    public function teacherStore() {
        Auth::require(['super_admin','admin']); Csrf::verify();
        $id = $this->input('id');
        $data = [
            'nip'=>trim($this->input('nip','')),'name'=>trim($this->input('name','')),
            'gender'=>$this->input('gender','L'),'phone'=>trim($this->input('phone','')),
            'whatsapp'=>\App\Services\WhatsAppService::normalizePhone($this->input('whatsapp','')),
            'email'=>trim($this->input('email','')),'address'=>trim($this->input('address','')),
        ];
        if ($id) Database::update('teachers',$data,'id=:id',['id'=>$id]);
        else Database::insert('teachers',$data);
        flash('success','Guru disimpan.'); redirect('/teachers');
    }
    public function teacherDelete($id) {
        Auth::require(['super_admin','admin']); Csrf::verify();
        Database::query("DELETE FROM teachers WHERE id=?", [$id]);
        flash('success','Guru dihapus.'); redirect('/teachers');
    }
}
