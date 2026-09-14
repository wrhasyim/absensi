<?php
namespace App\Services;

use App\Core\Database;
use App\Services\Fingerprint\MockAdapter;
use App\Services\Fingerprint\SolutionX100CAdapter;
use App\Services\WhatsAppService;

class FingerprintService {
    private array $device;

    public function __construct(array $device) {
        $this->device = $device;
    }

    private function getAdapter() {
        $config = [
            'ip'   => $this->device['ip_address'] ?? '',
            'port' => $this->device['port'] ?? 4370,
        ];
        if (($this->device['adapter'] ?? 'mock') === 'x100c') {
            return new SolutionX100CAdapter($config);
        }
        return new MockAdapter($config);
    }

    public function testConnection(): array {
        $adapter = $this->getAdapter();
        return $adapter->testConnection();
    }

    public function synchronize(): array {
        $adapter = $this->getAdapter();
        
        if (!$adapter->connect()) {
            return ['success' => false, 'message' => 'Gagal terhubung ke perangkat fingerprint.'];
        }

        $logs = $adapter->getAttendanceLogs();
        $adapter->disconnect();

        $newLogsCount = 0; $duplicateLogsCount = 0; $attendancesProcessed = 0; $waQueueCount = 0;

        foreach ($logs as $log) {
            $fpId = $log['fingerprint_id'] ?? null;
            $timestamp = $log['timestamp'] ?? null;
            if (!$fpId || !$timestamp) continue;

            $logDate = date('Y-m-d', strtotime($timestamp));
            $logTime = date('H:i:s', strtotime($timestamp));

            // 1. CEK STATUS: APAKAH INI SISWA ATAU GURU?
            $student = Database::fetch("SELECT id, class_id, name, whatsapp, parent_id FROM students WHERE fingerprint_id = ? AND status = 'aktif'", [$fpId]);
            $teacher = null;
            
            if (!$student) {
                // Jika bukan siswa, cari di tabel guru
                $teacher = Database::fetch("SELECT id, name, phone as whatsapp, leader_id FROM teachers WHERE fingerprint_id = ?", [$fpId]);
            }

            // Abaikan log jika sidik jari tidak terdaftar sebagai siswa maupun guru
            if (!$student && !$teacher) continue;

            $studentId = $student ? $student['id'] : null;
            $teacherId = $teacher ? $teacher['id'] : null;

            // 2. SIMPAN LOG
            $existingLog = Database::fetch("SELECT id FROM fingerprint_logs WHERE device_id = ? AND fingerprint_id = ? AND log_datetime = ?", [$this->device['id'], $fpId, $timestamp]);

            if (!$existingLog) {
                Database::insert('fingerprint_logs', [
                    'device_id'      => $this->device['id'],
                    'student_id'     => $studentId,
                    'teacher_id'     => $teacherId,
                    'fingerprint_id' => $fpId,
                    'log_datetime'   => $timestamp,
                    'raw_data'       => $log['raw'] ?? null,
                ]);
                $newLogsCount++;
            } else {
                $duplicateLogsCount++;
            }

            // 3. PROSES ABSENSI
            $attResult = $this->processAttendance($student, $teacher, $logDate, $logTime);
            if ($attResult['processed']) {
                $attendancesProcessed++;
                if ($attResult['wa_queued']) $waQueueCount++;
            }
        }

        Database::update('devices', ['last_sync' => date('Y-m-d H:i:s'), 'status' => 'online'], 'id = :id', ['id' => $this->device['id']]);

        return [
            'success' => true,
            'message' => "Berhasil: {$newLogsCount} log, {$duplicateLogsCount} duplikat, {$attendancesProcessed} absensi, {$waQueueCount} WA queue.",
        ];
    }

    private function processAttendance(?array $student, ?array $teacher, string $date, string $time): array {
        $studentId = $student ? $student['id'] : null;
        $teacherId = $teacher ? $teacher['id'] : null;
        $classId   = $student ? $student['class_id'] : null;

        // Query berbeda tergantung dia siswa atau guru
        if ($student) {
            $existingAtt = Database::fetch("SELECT id, time_in, time_out, status FROM attendances WHERE student_id = ? AND attendance_date = ?", [$studentId, $date]);
        } else {
            $existingAtt = Database::fetch("SELECT id, time_in, time_out, status FROM attendances WHERE teacher_id = ? AND attendance_date = ?", [$teacherId, $date]);
        }

        $processed = false; $waQueued = false;

        $settingJamMasuk = Database::fetch("SELECT setting_value FROM school_settings WHERE setting_key = 'jam_masuk'");
        $settingToleransi = Database::fetch("SELECT setting_value FROM school_settings WHERE setting_key = 'toleransi_menit'");
        $jamMasuk = trim($settingJamMasuk['setting_value'] ?? '07:00:00');
        if (strlen($jamMasuk) === 5) $jamMasuk .= ':00';
        
        $toleransi = (int)($settingToleransi['setting_value'] ?? 0);
        $timeMasukTimestamp = strtotime($date . ' ' . $jamMasuk);
        $timeBatasTimestamp = $timeMasukTimestamp + ($toleransi * 60);
        $timeScanTimestamp = strtotime($date . ' ' . $time);

        if (!$existingAtt) {
            $status = ($timeScanTimestamp <= $timeBatasTimestamp) ? 'hadir' : 'terlambat';
            $attendanceId = Database::insert('attendances', [
                'student_id'      => $studentId,
                'teacher_id'      => $teacherId,
                'class_id'        => $classId,
                'device_id'       => $this->device['id'],
                'attendance_date' => $date,
                'time_in'         => $time,
                'status'          => $status,
                'created_at'      => date('Y-m-d H:i:s')
            ]);
            $processed = true;
            $waQueued = $this->enqueueWhatsapp($student, $teacher, $date, $time, $status, 'masuk');

        } else if (empty($existingAtt['time_out']) && $timeScanTimestamp > strtotime($date . ' ' . $existingAtt['time_in'])) {
            Database::update('attendances', ['time_out' => $time], 'id = :id', ['id' => $existingAtt['id']]);
            $processed = true;
            $waQueued = $this->enqueueWhatsapp($student, $teacher, $date, $time, $existingAtt['status'] ?? 'hadir', 'pulang');
        }

        return ['processed' => $processed, 'wa_queued' => $waQueued];
    }

    private function enqueueWhatsapp(?array $student, ?array $teacher, string $date, string $time, string $status, string $type): bool {
        $recipients = [];

        // 1. TENTUKAN PENERIMA BERDASARKAN ROLE (Siswa / Guru)
        if ($student) {
            // Target Notifikasi Siswa -> Orang Tua & Siswa
            if (!empty($student['parent_id'])) {
                $parent = Database::fetch("SELECT primary_whatsapp, father_whatsapp, mother_whatsapp, guardian_whatsapp, is_active FROM parents WHERE id = ?", [$student['parent_id']]);
                if ($parent && ($parent['is_active'] ?? 1) == 1) {
                    if (!empty($parent['primary_whatsapp'])) $recipients[] = $parent['primary_whatsapp'];
                    if (!empty($parent['father_whatsapp']))  $recipients[] = $parent['father_whatsapp'];
                    if (!empty($parent['mother_whatsapp']))  $recipients[] = $parent['mother_whatsapp'];
                }
            }
            if (!empty($student['whatsapp'])) $recipients[] = $student['whatsapp'];
            
        } else if ($teacher) {
            // Target Notifikasi Guru -> Pimpinan (Kepsek, Wakasek, WA Utama)
            if (!empty($teacher['leader_id'])) {
                $leader = Database::fetch("SELECT kepsek_wa, wakasek_wa, primary_whatsapp, is_active FROM leaders WHERE id = ?", [$teacher['leader_id']]);
                if ($leader && ($leader['is_active'] ?? 1) == 1) {
                    if (!empty($leader['primary_whatsapp'])) $recipients[] = $leader['primary_whatsapp'];
                    if (!empty($leader['kepsek_wa'])) $recipients[] = $leader['kepsek_wa'];
                    if (!empty($leader['wakasek_wa'])) $recipients[] = $leader['wakasek_wa'];
                }
            }
            if (!empty($teacher['whatsapp'])) $recipients[] = $teacher['whatsapp'];
        }

        // 2. NORMALISASI NOMOR (Mencegah pengiriman ganda)
        $normalizedPhones = [];
        foreach ($recipients as $rawPhone) {
            $cleaned = WhatsAppService::normalizePhone($rawPhone);
            if (!empty($cleaned) && strlen($cleaned) >= 10 && !in_array($cleaned, $normalizedPhones)) {
                $normalizedPhones[] = $cleaned;
            }
        }
        if (empty($normalizedPhones)) return false;

        // 3. AMBIL TEMPLATE SESUAI ROLE
        if ($student) {
            // Template Siswa
            $templateCode = ($type === 'masuk') ? (($status === 'terlambat') ? 'attendance_late' : 'ATTENDANCE_IN') : 'ATTENDANCE_OUT';
            $classRow = Database::fetch("SELECT name FROM classes WHERE id = ?", [$student['class_id']]);
            $className = $classRow['name'] ?? '-';
            
            $template = Database::fetch("SELECT content FROM whatsapp_templates WHERE code = ? AND is_active = 1", [$templateCode]);
            
            if (!$template) {
                $message = "Info Absensi: Siswa {$student['name']} (Kelas {$className}) telah absensi {$type} pada {$date} jam {$time}. Status: " . strtoupper($status);
            } else {
                $message = str_replace(
                    ['{nama_siswa}', '{kelas}', '{tanggal}', '{jam}', '{status}', '{tipe}'],
                    [$student['name'], $className, date('d M Y', strtotime($date)), substr($time, 0, 5), strtoupper($status), strtoupper($type)],
                    $template['content']
                );
            }
        } else {
            // Template Guru
            $templateCode = ($type === 'masuk') ? 'TEACHER_IN' : 'TEACHER_OUT';
            $template = Database::fetch("SELECT content FROM whatsapp_templates WHERE code = ? AND is_active = 1", [$templateCode]);
            
            if (!$template) {
                $message = "Laporan Kehadiran: Guru {$teacher['name']} telah melakukan absensi {$type} pada {$date} jam {$time} WIB.";
            } else {
                $message = str_replace(
                    ['{nama_guru}', '{tanggal}', '{jam}', '{tipe}'],
                    [$teacher['name'], date('d M Y', strtotime($date)), substr($time, 0, 5), strtoupper($type)],
                    $template['content']
                );
            }
        }

        // 4. MASUKKAN KE ANTARAAN WA
        $queuedAny = false;
        foreach ($normalizedPhones as $phone) {
            // Cek pencegahan duplikat spam di hari yang sama
            $idField = $student ? 'student_id' : 'teacher_id';
            $idVal   = $student ? $student['id'] : $teacher['id'];
            
            $existQueue = Database::fetch("SELECT id FROM whatsapp_queue WHERE phone = ? AND message = ? AND created_at LIKE ?", [$phone, $message, $date . '%']);

            if (!$existQueue) {
                Database::insert('whatsapp_queue', [
                    $idField       => $idVal,
                    'phone'        => $phone,
                    'message'      => $message,
                    'status'       => 'pending',
                    'scheduled_at' => date('Y-m-d H:i:s'),
                    'created_at'   => date('Y-m-d H:i:s')
                ]);
                $queuedAny = true;
            }
        }

        return $queuedAny;
    }
}