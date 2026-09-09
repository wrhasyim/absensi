<?php
require_once __DIR__ . '/../../config/config.php';
use App\Core\Database;

// Load & execute schema
$schema = file_get_contents(__DIR__ . '/../migrations/schema.sql');
$pdo = Database::conn();
foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
    if ($stmt) $pdo->exec($stmt);
}
echo "Schema OK\n";

// Users
$hash = password_hash('admin123', PASSWORD_BCRYPT);
Database::insert('users', ['username'=>'admin','password'=>$hash,'name'=>'Super Admin','email'=>'admin@sekolah.sch.id','role'=>'super_admin']);
Database::insert('users', ['username'=>'operator','password'=>password_hash('operator123', PASSWORD_BCRYPT),'name'=>'Operator TU','role'=>'admin']);
Database::insert('users', ['username'=>'guru1','password'=>password_hash('guru123', PASSWORD_BCRYPT),'name'=>'Pak Budi (Wali X TKJ 1)','role'=>'guru']);
Database::insert('users', ['username'=>'kepsek','password'=>password_hash('kepsek123', PASSWORD_BCRYPT),'name'=>'Ibu Siti (Kepala Sekolah)','role'=>'kepala_sekolah']);

// Majors
$tkjId = Database::insert('majors', ['code'=>'TKJ','name'=>'Teknik Komputer dan Jaringan','description'=>'Jaringan Komputer & Sistem']);
$rplId = Database::insert('majors', ['code'=>'RPL','name'=>'Rekayasa Perangkat Lunak','description'=>'Pemrograman & Software']);

// Classes
$cls = [];
foreach ([['X TKJ 1','X',$tkjId],['XI TKJ 1','XI',$tkjId],['X RPL 1','X',$rplId],['XI RPL 1','XI',$rplId]] as $c) {
    $cls[] = Database::insert('classes', ['name'=>$c[0],'grade'=>$c[1],'major_id'=>$c[2]]);
}

// Teachers
Database::insert('teachers', ['nip'=>'19850101','name'=>'Pak Budi','gender'=>'L','phone'=>'081234567890','whatsapp'=>'6281234567890','email'=>'budi@sekolah.sch.id']);
Database::insert('teachers', ['nip'=>'19860202','name'=>'Bu Rina','gender'=>'P','phone'=>'081234567891','whatsapp'=>'6281234567891','email'=>'rina@sekolah.sch.id']);

// Parents + Students
$namaSiswa = ['Andi Pratama','Budi Santoso','Citra Dewi','Dedi Kurniawan','Eka Putri','Fajar Nugroho','Gita Rahma','Hadi Wijaya','Intan Sari','Joko Susilo',
              'Kartika Ayu','Lukman Hakim','Maya Anggraini','Nanda Putra','Oki Setiawan','Putri Ramadhani','Rian Firmansyah','Siti Nurhaliza','Tono Prasetyo','Umi Kalsum'];
foreach ($namaSiswa as $i => $nama) {
    $pid = Database::insert('parents', [
        'father_name' => 'Bapak ' . explode(' ', $nama)[0],
        'father_whatsapp' => '628111100' . str_pad($i+1, 3, '0', STR_PAD_LEFT),
        'mother_name' => 'Ibu ' . explode(' ', $nama)[0],
        'mother_whatsapp' => '628111200' . str_pad($i+1, 3, '0', STR_PAD_LEFT),
        'primary_whatsapp' => '628111100' . str_pad($i+1, 3, '0', STR_PAD_LEFT),
        'relation' => 'Orang Tua',
    ]);
    $classId = $cls[$i % 4];
    $majorId = ($i % 4 < 2) ? $tkjId : $rplId;
    Database::insert('students', [
        'nis' => '2026' . str_pad($i+1, 4, '0', STR_PAD_LEFT),
        'nisn' => '00' . str_pad($i+1, 8, '0', STR_PAD_LEFT),
        'name' => $nama,
        'nickname' => explode(' ', $nama)[0],
        'gender' => $i % 2 ? 'P' : 'L',
        'birth_place' => 'Jakarta',
        'birth_date' => date('Y-m-d', strtotime('-' . (15 + ($i%3)) . ' years')),
        'address' => 'Jl. Merdeka No. ' . ($i+1),
        'phone' => '0813' . rand(10000000, 99999999),
        'whatsapp' => '62813' . rand(10000000, 99999999),
        'class_id' => $classId,
        'major_id' => $majorId,
        'entry_year' => 2024,
        'status' => 'aktif',
        'fingerprint_id' => (string)(1000 + $i + 1),
        'parent_id' => $pid,
    ]);
}

// Device
Database::insert('devices', ['name'=>'Solution X100C - Gerbang','ip_address'=>'192.168.1.201','port'=>4370,'location'=>'Gerbang Utama','adapter'=>'mock','status'=>'unknown','is_active'=>1]);

// School settings
foreach ([
    'jam_masuk' => '07:00',
    'toleransi_menit' => '15',
    'batas_belum_absen' => '08:00',
    'jam_pulang' => '15:00',
    'nama_sekolah' => 'SMK Negeri Absensi',
    'notif_terlambat' => '1',
    'notif_pulang' => '0',
] as $k => $v) {
    Database::insert('school_settings', ['setting_key'=>$k,'setting_value'=>$v]);
}

// WhatsApp templates
Database::insert('whatsapp_templates', [
    'code'=>'attendance_present','name'=>'Absensi Hadir/Masuk',
    'content'=>"Assalamu'alaikum Bapak/Ibu.\n\nKami informasikan bahwa putra/putri Anda:\n\nNama: {nama_siswa}\nKelas: {kelas}\nTanggal: {tanggal}\nJam masuk: {jam}\nStatus: {status}\n\ntelah melakukan absensi di sekolah.\n\nTerima kasih.",
    'is_active'=>1,
]);
Database::insert('whatsapp_templates', [
    'code'=>'attendance_late','name'=>'Absensi Terlambat',
    'content'=>"Assalamu'alaikum Bapak/Ibu.\n\nPutra/putri Anda:\n\nNama: {nama_siswa}\nKelas: {kelas}\n\ntelah melakukan absensi pada:\nTanggal: {tanggal}\nJam: {jam}\n\nStatus: TERLAMBAT.\n\nMohon menjadi perhatian.\n\nTerima kasih.",
    'is_active'=>1,
]);
Database::insert('whatsapp_templates', [
    'code'=>'attendance_absent','name'=>'Belum Absen',
    'content'=>"Assalamu'alaikum Bapak/Ibu.\n\nSampai pukul {jam}, putra/putri Anda:\n\nNama: {nama_siswa}\nKelas: {kelas}\n\nbelum tercatat melakukan absensi masuk hari ini.\n\nMohon konfirmasi kepada pihak sekolah apabila siswa tidak masuk.",
    'is_active'=>1,
]);
Database::insert('whatsapp_templates', [
    'code'=>'attendance_out','name'=>'Absensi Pulang',
    'content'=>"Putra/putri Anda:\n\nNama: {nama_siswa}\nKelas: {kelas}\n\ntelah melakukan absensi pulang pada pukul {jam}.\n\nTerima kasih.",
    'is_active'=>1,
]);

// Sample attendance today
$students = Database::fetchAll("SELECT id, class_id FROM students LIMIT 12");
$today = date('Y-m-d');
foreach ($students as $i => $s) {
    $time = $i < 8 ? sprintf('06:%02d:00', 30 + $i * 2) : sprintf('07:%02d:00', 20 + $i);
    $status = strtotime($time) <= strtotime('07:15:00') ? 'hadir' : 'terlambat';
    try {
        Database::insert('attendances', [
            'student_id'=>$s['id'],'class_id'=>$s['class_id'],'attendance_date'=>$today,
            'time_in'=>$time,'status'=>$status,
        ]);
    } catch (\Throwable $e) {}
}

echo "Seed OK. Login: admin / admin123\n";
