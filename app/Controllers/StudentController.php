<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Csrf;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

    public function importExcel() {
        Auth::require(['super_admin', 'admin']);
        Csrf::verify();

        if (isset($_FILES['file_excel']['name']) && $_FILES['file_excel']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['file_excel']['tmp_name'];
            $extension = pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION);

            if (in_array($extension, ['xls', 'xlsx'])) {
                try {
                    $spreadsheet = IOFactory::load($file_tmp);
                    $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                    $berhasil = 0;
                    $gagal = 0;
                    $lastError = "";

                    for ($i = 2; $i <= count($sheetData); $i++) {
                        $nis = trim($sheetData[$i]['A'] ?? '');
                        $nama_lengkap = trim($sheetData[$i]['C'] ?? '');
                        
                        // Lewati jika NIS atau Nama Lengkap kosong
                        if (empty($nis) || empty($nama_lengkap)) {
                            continue;
                        }

                        $nisn = trim($sheetData[$i]['B'] ?? '');
                        $nama_panggilan = trim($sheetData[$i]['D'] ?? '');
                        $jenis_kelamin = (strtoupper(trim($sheetData[$i]['E'] ?? '')) === 'P') ? 'P' : 'L';
                        $tempat_lahir = trim($sheetData[$i]['F'] ?? '');
                        $tanggal_lahir = trim($sheetData[$i]['G'] ?? '') ?: null;
                        $tahun_masuk = trim($sheetData[$i]['H'] ?? '') ?: null;
                        
                        // Pencocokan otomatis Kelas
                        $input_kelas = trim($sheetData[$i]['I'] ?? '');
                        $class_id = null;
                        if (!empty($input_kelas)) {
                            $kelasData = Database::fetch("SELECT id FROM classes WHERE name LIKE ? OR id = ?", ["%$input_kelas%", $input_kelas]);
                            if ($kelasData) { $class_id = $kelasData['id']; }
                        }

                        // Pencocokan otomatis Jurusan
                        $input_jurusan = trim($sheetData[$i]['J'] ?? '');
                        $major_id = null;
                        if (!empty($input_jurusan)) {
                            $jurusanData = Database::fetch("SELECT id FROM majors WHERE name LIKE ? OR code LIKE ? OR id = ?", ["%$input_jurusan%", "%$input_jurusan%", $input_jurusan]);
                            if ($jurusanData) { $major_id = $jurusanData['id']; }
                        }

                        $fingerprint_id = trim($sheetData[$i]['K'] ?? '') ?: null;
                        $status_siswa = trim($sheetData[$i]['L'] ?? '') ?: 'aktif';
                        $phone = trim($sheetData[$i]['M'] ?? '');
                        $whatsapp = \App\Services\WhatsAppService::normalizePhone(trim($sheetData[$i]['N'] ?? ''));
                        $address = trim($sheetData[$i]['O'] ?? '');

                        // Data Orang Tua
                        $father_name = trim($sheetData[$i]['P'] ?? '');
                        $father_wa = \App\Services\WhatsAppService::normalizePhone(trim($sheetData[$i]['Q'] ?? ''));
                        $mother_name = trim($sheetData[$i]['R'] ?? '');
                        $mother_wa = \App\Services\WhatsAppService::normalizePhone(trim($sheetData[$i]['S'] ?? ''));
                        $guardian_name = trim($sheetData[$i]['T'] ?? '');
                        $guardian_wa = \App\Services\WhatsAppService::normalizePhone(trim($sheetData[$i]['U'] ?? ''));
                        $primary_wa = \App\Services\WhatsAppService::normalizePhone(trim($sheetData[$i]['V'] ?? ''));
                        $relation = trim($sheetData[$i]['W'] ?? '') ?: 'Orang Tua';
                        $is_active_ortu = (strtolower(trim($sheetData[$i]['X'] ?? '')) === 'nonaktif') ? 0 : 1;

                        $parent_id = null;

                        // Cek dan Simpan / Hubungkan Data Orang Tua
                        if (!empty($primary_wa)) {
                            try {
                                // Cek apakah nomor WA utama sudah terdaftar di tabel parents
                                $existingParent = Database::fetch("SELECT id FROM parents WHERE primary_whatsapp = ?", [$primary_wa]);
                                
                                if ($existingParent) {
                                    // Jika sudah ada, gunakan ID yang lama
                                    $parent_id = $existingParent['id'];
                                } else {
                                    // Jika belum ada, buat baru
                                    $parent_id = Database::insert('parents', [
                                        'father_name' => $father_name,
                                        'father_whatsapp' => $father_wa,
                                        'mother_name' => $mother_name,
                                        'mother_whatsapp' => $mother_wa,
                                        'guardian_name' => $guardian_name,
                                        'guardian_whatsapp' => $guardian_wa,
                                        'primary_whatsapp' => $primary_wa,
                                        'relation' => $relation,
                                        'is_active' => $is_active_ortu
                                    ]);
                                }
                            } catch (\Throwable $e) {
                                $lastError = "Ortu: " . $e->getMessage();
                            }
                        }

                        // Insert data Siswa
                        try {
                            Database::insert('students', [
                                'nis' => $nis,
                                'nisn' => $nisn,
                                'name' => $nama_lengkap,
                                'nickname' => $nama_panggilan,
                                'gender' => $jenis_kelamin,
                                'birth_place' => $tempat_lahir,
                                'birth_date' => $tanggal_lahir,
                                'entry_year' => $tahun_masuk,
                                'class_id' => $class_id,
                                'major_id' => $major_id,
                                'fingerprint_id' => $fingerprint_id,
                                'status' => $status_siswa,
                                'phone' => $phone,
                                'whatsapp' => $whatsapp,
                                'parent_id' => $parent_id,
                                'address' => $address
                            ]);
                            $berhasil++;
                        } catch (\Throwable $e) {
                            $gagal++;
                            $lastError = "Siswa (NIS $nis): " . $e->getMessage();
                        }
                    }

                    Auth::logActivity('import_students', "Berhasil: $berhasil, Gagal: $gagal");
                    
                    if ($gagal > 0) {
                        flash('error', "Berhasil: $berhasil. Gagal: $gagal baris. Cek Error Terakhir: " . $lastError);
                    } else {
                        flash('success', "Import selesai! Berhasil menyimpan $berhasil siswa beserta data orang tua.");
                    }
                    
                } catch (\Throwable $e) {
                    flash('error', "Terjadi kesalahan sistem saat membaca file: " . $e->getMessage());
                }
            } else {
                flash('error', "Format file tidak didukung. Harap gunakan format .xlsx atau .xls");
            }
        } else {
            flash('error', "Gagal mengunggah file.");
        }
        
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