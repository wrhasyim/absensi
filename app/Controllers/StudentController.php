<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Csrf;

class StudentController extends Controller {
    public function index() {
        Auth::require();
        $q = trim($this->input('q', ''));
        $classId = $this->input('class_id', '');
        $where = "1=1";
        $params = [];
        if ($q) {
            $where .= " AND (s.name LIKE ? OR s.nis LIKE ? OR s.nisn LIKE ?)";
            $like = "%$q%";
            $params = [$like, $like, $like];
        }
        if ($classId !== '') {
            $where .= " AND s.class_id = ?";
            $params[] = $classId;
        }
        $students = Database::fetchAll("
            SELECT s.*, c.name class_name, m.code major_code
            FROM students s
            LEFT JOIN classes c ON c.id = s.class_id
            LEFT JOIN majors m ON m.id = s.major_id
            WHERE $where
            ORDER BY s.name
            LIMIT 200
        ", $params);
        $classes = Database::fetchAll("SELECT * FROM classes ORDER BY name");
        $this->view('students.index', compact('students', 'classes', 'q', 'classId') + ['title' => 'Data Siswa']);
    }

    public function create() {
        Auth::require(['super_admin', 'admin']);
        $classes = Database::fetchAll("SELECT * FROM classes ORDER BY name");
        $majors = Database::fetchAll("SELECT * FROM majors ORDER BY name");
        $parents = Database::fetchAll("SELECT * FROM parents ORDER BY father_name, mother_name");
        $this->view('students.form', compact('classes', 'majors', 'parents') + ['title' => 'Tambah Siswa', 'student' => null]);
    }

    public function store() {
        Auth::require(['super_admin', 'admin']);
        Csrf::verify();
        $data = $this->collectData();
        try {
            $id = Database::insert('students', $data);
            Auth::logActivity('create_student', "NIS: {$data['nis']}");
            flash('success', 'Siswa berhasil ditambahkan.');
        } catch (\Throwable $e) {
            flash('error', 'Gagal: ' . $e->getMessage());
            redirect('/students/create');
        }
        redirect('/students');
    }

    public function edit($id) {
        Auth::require(['super_admin', 'admin']);
        $student = Database::fetch("SELECT * FROM students WHERE id=?", [$id]);
        if (!$student) { flash('error', 'Data siswa tidak ditemukan.'); redirect('/students'); }
        $classes = Database::fetchAll("SELECT * FROM classes ORDER BY name");
        $majors = Database::fetchAll("SELECT * FROM majors ORDER BY name");
        $parents = Database::fetchAll("SELECT * FROM parents ORDER BY father_name");
        $this->view('students.form', compact('student', 'classes', 'majors', 'parents') + ['title' => 'Edit Siswa']);
    }

    public function update($id) {
        Auth::require(['super_admin', 'admin']);
        Csrf::verify();
        $data = $this->collectData();
        try {
            Database::update('students', $data, 'id=:id', ['id' => $id]);
            Auth::logActivity('update_student', "ID: $id");
            flash('success', 'Siswa berhasil diupdate.');
        } catch (\Throwable $e) {
            flash('error', 'Gagal: ' . $e->getMessage());
        }
        redirect('/students');
    }

    public function delete($id) {
        Auth::require(['super_admin', 'admin']);
        Csrf::verify();
        Database::query("DELETE FROM students WHERE id=?", [$id]);
        Auth::logActivity('delete_student', "ID: $id");
        flash('success', 'Siswa dihapus.');
        redirect('/students');
    }

    private function collectData(): array {
        return [
            'nis' => trim($this->input('nis', '')),
            'nisn' => trim($this->input('nisn', '')),
            'name' => trim($this->input('name', '')),
            'nickname' => trim($this->input('nickname', '')),
            'gender' => $this->input('gender', 'L'),
            'birth_place' => trim($this->input('birth_place', '')),
            'birth_date' => $this->input('birth_date') ?: null,
            'address' => trim($this->input('address', '')),
            'phone' => trim($this->input('phone', '')),
            'whatsapp' => \App\Services\WhatsAppService::normalizePhone($this->input('whatsapp', '')),
            'class_id' => $this->input('class_id') ?: null,
            'major_id' => $this->input('major_id') ?: null,
            'entry_year' => $this->input('entry_year') ?: null,
            'status' => $this->input('status', 'aktif'),
            'fingerprint_id' => trim($this->input('fingerprint_id', '')) ?: null,
            'parent_id' => $this->input('parent_id') ?: null,
        ];
    }
}
