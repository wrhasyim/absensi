<?php
/**
 * Cron: Cek siswa yang belum absen setelah batas waktu, kirim notifikasi orang tua.
 * Jalankan tiap 10 menit setelah jam masuk.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/WhatsAppService.php';
use App\Core\Database;

// Skip kalau hari libur
$today = date('Y-m-d');
$holiday = Database::fetch("SELECT id FROM holidays WHERE holiday_date=?", [$today]);
if ($holiday) { echo "Hari libur, skip.\n"; exit; }

$settings = [];
foreach (Database::fetchAll("SELECT setting_key, setting_value FROM school_settings") as $r) $settings[$r['setting_key']]=$r['setting_value'];
$batas = $settings['batas_belum_absen'] ?? '08:00';
if (date('H:i') < $batas) { echo "Belum melewati batas.\n"; exit; }

$template = Database::fetch("SELECT * FROM whatsapp_templates WHERE code='attendance_absent' AND is_active=1");
if (!$template) { echo "Template tidak aktif.\n"; exit; }

$students = Database::fetchAll("
    SELECT s.*, c.name class_name, p.primary_whatsapp
    FROM students s
    LEFT JOIN classes c ON c.id=s.class_id
    LEFT JOIN parents p ON p.id=s.parent_id
    WHERE s.status='aktif'
      AND s.id NOT IN (SELECT student_id FROM attendances WHERE attendance_date=?)
", [$today]);

$svc = new \App\Services\WhatsAppService();
$queued = 0;
foreach ($students as $s) {
    if (!$s['primary_whatsapp']) continue;
    // Anti-duplicate
    $exists = Database::fetch("SELECT id FROM whatsapp_queue WHERE student_id=? AND template_code='attendance_absent' AND DATE(created_at)=?", [$s['id'],$today]);
    if ($exists) continue;
    $msg = $svc->render($template['content'], [
        'nama_siswa' => $s['name'],
        'kelas' => $s['class_name'] ?? '-',
        'jam' => date('H:i'),
        'tanggal' => date('d M Y'),
    ]);
    Database::insert('whatsapp_queue', [
        'student_id' => $s['id'],
        'phone' => \App\Services\WhatsAppService::normalizePhone($s['primary_whatsapp']),
        'message' => $msg,
        'template_code' => 'attendance_absent',
        'status' => 'pending',
    ]);
    $queued++;
}
echo date('Y-m-d H:i:s') . " | belum_absen_queued=$queued\n";
