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

        set_time_limit(300); 

        $logs = $adapter->getAttendanceLogs();
        $adapter->disconnect();

        if (!is_array($logs)) {
            return ['success' => false, 'message' => 'Gagal menarik log. Koneksi terputus atau memori mesin X100C kepenuhan.'];
        }

        $newLogsCount = 0; $duplicateLogsCount = 0; $attendancesProcessed = 0; $waQueueCount = 0;
        $today = date('Y-m-d'); 

        foreach ($logs as $log) {
            $fpId = $log['fingerprint_id'] ?? null;
            $timestamp = $log['timestamp'] ?? null;
            
            if (!$fpId || !$timestamp || $timestamp === '0000-00-00 00:00:00') continue;

            $parsedTime = strtotime($timestamp);
            if (!$parsedTime) continue;

            $logDate = date('Y-m-d', $parsedTime);
            $logTime = date('H:i:s', $parsedTime);

            // PENCARIAN GURU & SISWA DENGAN PELINDUNG PHP 8
            $studentRes = Database::fetch("SELECT id, class_id, name, whatsapp, parent_id FROM students WHERE fingerprint_id = ? AND status = 'aktif'", [$fpId]);
            $student = is_array($studentRes) ? $studentRes : null;
            
            $teacher = null;
            if (!$student) {
                $teacherRes = Database::fetch("SELECT id, name, phone as whatsapp, leader_id FROM teachers WHERE fingerprint_id = ?", [$fpId]);
                $teacher = is_array($teacherRes) ? $teacherRes : null;
            }

            if (!$student && !$teacher) continue;

            $studentId = $student ? $student['id'] : null;
            $teacherId = $teacher ? $teacher['id'] : null;

            // SIMPAN LOG
            $existingLog = Database::fetch("SELECT id FROM fingerprint_logs WHERE device_id = ? AND fingerprint_id = ? AND log_datetime = ?", [$this->device['id'], $fpId, $timestamp]);

            if (!$existingLog) {
                Database::insert('fingerprint_logs', [
                    'device_id'      => $this->device['id'],
                    'student_id'     => $studentId,
                    'teacher_id'     => $teacherId,
                    'fingerprint_id' => $fpId,
                    'log_date'       => $logDate, 
                    'log_time'       => $logTime, 
                    'log_datetime'   => $timestamp,
                    'raw_data'       => $log['raw'] ?? null,
                    'synced_at'      => date('Y-m-d H:i:s')
                ]);
                $newLogsCount++;
            } else {
                $duplicateLogsCount++;
                Database::update('fingerprint_logs', ['synced_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $existingLog['id']]);
            }

            // PROSES ABSENSI UNTUK HARI INI
            if ($logDate === $today) {
                $attResult = $this->processAttendance($student, $teacher, $logDate, $logTime);
                if ($attResult['processed']) {
                    $attendancesProcessed++;
                    if ($attResult['wa_queued']) $waQueueCount++;
                }
            }
        }

        Database::update('devices', ['last_sync' => date('Y-m-d H:i:s'), 'status' => 'online'], 'id = :id', ['id' => $this->device['id']]);

        return [
            'success' => true,
            'message' => "Berhasil: {$newLogsCount} log baru, {$duplicateLogsCount} duplikat, {$attendancesProcessed} absensi diproses, {$waQueueCount} pesan WA masuk antrean.",
            'details' => [
                'new_logs'              => $newLogsCount,
                'duplicate_logs'        => $duplicateLogsCount,
                'attendances_processed' => $attendancesProcessed,
                'wa_queue_count'        => $waQueueCount
            ]
        ];
    }

    // TYPE HINT DIHAPUS AGAR KEBAL FATAL ERROR
    private function processAttendance($student, $teacher, string $date, string $time): array {
        $studentId = $student ? $student['id'] : null;
        $teacherId = $teacher ? $teacher['id'] : null;
        $classId   = $student ? $student['class_id'] : null;

        if ($student) {
            $existingAtt = Database::fetch("SELECT id, time_in, time_out, status FROM attendances WHERE student_id = ? AND attendance_date = ?", [$studentId, $date]);
        } else {
            $existingAtt = Database::fetch("SELECT id, time_in, time_out, status FROM attendances WHERE teacher_id = ? AND attendance_date = ?", [$teacherId, $date]);
        }
        $existingAtt = is_array($existingAtt) ? $existingAtt : null;

        $processed = false; $waQueued = false;

        $settingJamMasuk = Database::fetch("SELECT setting_value FROM school_settings WHERE setting_key = 'jam_masuk'");
        $settingToleransi = Database::fetch("SELECT setting_value FROM school_settings WHERE setting_key = 'toleransi_menit'");
        
        $jamMasuk = (is_array($settingJamMasuk) && !empty($settingJamMasuk['setting_value'])) ? trim($settingJamMasuk['setting_value']) : '07:00:00';
        if (strlen($jamMasuk) === 5) $jamMasuk .= ':00';
        
        $toleransi = (is_array($settingToleransi) && !empty($settingToleransi['setting_value'])) ? (int)$settingToleransi['setting_value'] : 0;
        
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

    private function enqueueWhatsapp($student, $teacher, string $date, string $time, string $status, string $type): bool {
        $recipients = [];

        if ($student) {
            if (!empty($student['parent_id'])) {
                $parent = Database::fetch("SELECT primary_whatsapp, father_whatsapp, mother_whatsapp, guardian_whatsapp, is_active FROM parents WHERE id = ?", [$student['parent_id']]);
                if (is_array($parent) && ($parent['is_active'] ?? 1) == 1) {
                    if (!empty($parent['primary_whatsapp'])) $recipients[] = $parent['primary_whatsapp'];
                    if (!empty($parent['father_whatsapp']))  $recipients[] = $parent['father_whatsapp'];
                    if (!empty($parent['mother_whatsapp']))  $recipients[] = $parent['mother_whatsapp'];
                }
            }
            if (!empty($student['whatsapp'])) $recipients[] = $student['whatsapp'];
            
        } else if ($teacher) {
            if (!empty($teacher['leader_id'])) {
                $leader = Database::fetch("SELECT kepsek_wa, wakasek_wa, primary_whatsapp, is_active FROM leaders WHERE id = ?", [$teacher['leader_id']]);
                if (is_array($leader) && ($leader['is_active'] ?? 1) == 1) {
                    if (!empty($leader['primary_whatsapp'])) $recipients[] = $leader['primary_whatsapp'];
                    if (!empty($leader['kepsek_wa'])) $recipients[] = $leader['kepsek_wa'];
                    if (!empty($leader['wakasek_wa'])) $recipients[] = $leader['wakasek_wa'];
                }
            }
            $teacherData = Database::fetch("SELECT whatsapp FROM teachers WHERE id = ?", [$teacher['id']]);
            if (is_array($teacherData) && !empty($teacherData['whatsapp'])) {
                $recipients[] = $teacherData['whatsapp'];
            }
        }

        $normalizedPhones = [];
        foreach ($recipients as $rawPhone) {
            $cleaned = WhatsAppService::normalizePhone($rawPhone);
            if (!empty($cleaned) && strlen($cleaned) >= 10 && !in_array($cleaned, $normalizedPhones)) {
                $normalizedPhones[] = $cleaned;
            }
        }
        if (empty($normalizedPhones)) return false;

        if ($student) {
            $templateCode = ($type === 'masuk') ? (($status === 'terlambat') ? 'attendance_late' : 'ATTENDANCE_IN') : 'ATTENDANCE_OUT';
            $classRow = Database::fetch("SELECT name FROM classes WHERE id = ?", [$student['class_id']]);
            $className = (is_array($classRow) && !empty($classRow['name'])) ? $classRow['name'] : '-';
            
            $template = Database::fetch("SELECT content FROM whatsapp_templates WHERE code = ? AND is_active = 1", [$templateCode]);
            
            if (!is_array($template) || empty($template['content'])) {
                $message = "Info Absensi: Siswa {$student['name']} (Kelas {$className}) telah melakukan absensi {$type} pada {$date} jam {$time}. Status: " . strtoupper($status);
            } else {
                $message = str_replace(
                    ['{nama_siswa}', '{kelas}', '{tanggal}', '{jam}', '{status}', '{tipe}'],
                    [$student['name'], $className, date('d M Y', strtotime($date)), substr($time, 0, 5), strtoupper($status), strtoupper($type)],
                    $template['content']
                );
            }
        } else {
            $templateCode = ($type === 'masuk') ? 'TEACHER_IN' : 'TEACHER_OUT';
            $template = Database::fetch("SELECT content FROM whatsapp_templates WHERE code = ? AND is_active = 1", [$templateCode]);
            
            if (!is_array($template) || empty($template['content'])) {
                $message = "Laporan Kehadiran: Guru {$teacher['name']} telah melakukan absensi {$type} pada {$date} jam {$time} WIB.";
            } else {
                $message = str_replace(
                    ['{nama_guru}', '{tanggal}', '{jam}', '{tipe}'],
                    [$teacher['name'], date('d M Y', strtotime($date)), substr($time, 0, 5), strtoupper($type)],
                    $template['content']
                );
            }
        }

        $queuedAny = false;
        foreach ($normalizedPhones as $phone) {
            $existQueue = Database::fetch("SELECT id FROM whatsapp_queue WHERE phone = ? AND message = ? AND DATE(created_at) = ?", [$phone, $message, $date]);

            if (!$existQueue) {
                Database::insert('whatsapp_queue', [
                    'student_id'   => $student ? $student['id'] : null,
                    'teacher_id'   => $teacher ? $teacher['id'] : null,
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