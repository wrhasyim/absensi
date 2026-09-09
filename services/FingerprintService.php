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

    /**
     * Mendapatkan adapter yang sesuai berdasarkan tipe perangkat
     */
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

    /**
     * Menguji koneksi ke mesin fingerprint
     */
    public function testConnection(): array {
        $adapter = $this->getAdapter();
        return $adapter->testConnection();
    }

    /**
     * Sinkronisasi log fingerprint dan konversi ke tabel attendances
     */
    public function synchronize(): array {
        $adapter = $this->getAdapter();
        
        if (!$adapter->connect()) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke perangkat fingerprint.',
            ];
        }

        // Ambil log dari mesin
        $logs = $adapter->getAttendanceLogs();
        $adapter->disconnect();

        $newLogsCount = 0;
        $duplicateLogsCount = 0;
        $attendancesProcessed = 0;
        $waQueueCount = 0;

        foreach ($logs as $log) {
            $fpId = $log['fingerprint_id'] ?? null;
            $timestamp = $log['timestamp'] ?? null;

            if (!$fpId || !$timestamp) {
                continue;
            }

            $logDate = date('Y-m-d', strtotime($timestamp));
            $logTime = date('H:i:s', strtotime($timestamp));

            // 1. Cari siswa berdasarkan fingerprint_id
            $student = Database::fetch("SELECT id, class_id, name, whatsapp, parent_id FROM students WHERE fingerprint_id = ? AND status = 'aktif'", [$fpId]);

            $studentId = $student ? $student['id'] : null;

            // 2. Simpan / Cek Log di fingerprint_logs
            $existingLog = Database::fetch("SELECT id FROM fingerprint_logs WHERE device_id = ? AND fingerprint_id = ? AND log_datetime = ?", [
                $this->device['id'],
                $fpId,
                $timestamp
            ]);

            if (!$existingLog) {
                Database::insert('fingerprint_logs', [
                    'device_id'      => $this->device['id'],
                    'student_id'     => $studentId,
                    'fingerprint_id' => $fpId,
                    'log_datetime'   => $timestamp,
                    'raw_data'       => $log['raw'] ?? null,
                ]);
                $newLogsCount++;
            } else {
                $duplicateLogsCount++;
            }

            // 3. PROSES ABSENSI (Berlaku untuk Log Baru MAUPUN Log Duplikat)
            if ($student) {
                $attResult = $this->processAttendance($student, $logDate, $logTime);
                
                if ($attResult['processed']) {
                    $attendancesProcessed++;
                    if ($attResult['wa_queued']) {
                        $waQueueCount++;
                    }
                }
            }
        }

        // Perbarui Waktu Sync Terakhir pada Perangkat
        Database::update('devices', [
            'last_sync' => date('Y-m-d H:i:s'),
            'status'    => 'online'
        ], 'id = :id', ['id' => $this->device['id']]);

        return [
            'success' => true,
            'message' => "Berhasil: {$newLogsCount} log baru, {$duplicateLogsCount} duplikat, {$attendancesProcessed} absensi, {$waQueueCount} WA queue.",
            'details' => [
                'new_logs'              => $newLogsCount,
                'duplicate_logs'        => $duplicateLogsCount,
                'attendances_processed' => $attendancesProcessed,
                'wa_queue_count'        => $waQueueCount
            ]
        ];
    }

    /**
     * Olah kehadiran siswa ke tabel attendances & buat antrean pesan WhatsApp
     */
    private function processAttendance(array $student, string $date, string $time): array {
        $studentId = $student['id'];
        $classId = $student['class_id'];

        // Cek record absensi siswa pada tanggal terkait
        $existingAtt = Database::fetch("SELECT id, time_in, time_out FROM attendances WHERE student_id = ? AND attendance_date = ?", [
            $studentId,
            $date
        ]);

        $processed = false;
        $waQueued = false;

        // 1. Ambil jam masuk & toleransi dari settings (key: 'toleransi_menit')
        $settingJamMasuk = Database::fetch("SELECT setting_value FROM school_settings WHERE setting_key = 'jam_masuk'");
        $settingToleransi = Database::fetch("SELECT setting_value FROM school_settings WHERE setting_key = 'toleransi_menit'");

        $jamMasuk = trim($settingJamMasuk['setting_value'] ?? '12:30:00');
        $toleransi = (int)($settingToleransi['setting_value'] ?? 0);

        // Pasang detik jika format di DB cuma '12:30'
        if (strlen($jamMasuk) === 5) {
            $jamMasuk .= ':00';
        }

        // 2. Hitung timestamp batas waktu masuk (Jam Masuk + Toleransi)
        $timeMasukTimestamp = strtotime($date . ' ' . $jamMasuk);
        $timeBatasTimestamp = $timeMasukTimestamp + ($toleransi * 60);

        // 3. Timestamp waktu scan fingerprint
        $timeScanTimestamp = strtotime($date . ' ' . $time);

        if (!$existingAtt) {
            // Tentukan status: HADIR jika waktu scan <= batas toleransi
            $status = ($timeScanTimestamp <= $timeBatasTimestamp) ? 'hadir' : 'terlambat';

            $attendanceId = Database::insert('attendances', [
                'student_id'      => $studentId,
                'class_id'        => $classId,
                'device_id'       => $this->device['id'],
                'attendance_date' => $date,
                'time_in'         => $time,
                'status'          => $status,
                'created_at'      => date('Y-m-d H:i:s')
            ]);

            $processed = true;

            // Masukkan ke Antrean WhatsApp
            $waQueued = $this->enqueueWhatsapp($student, $date, $time, $status, 'masuk');

        } else if (empty($existingAtt['time_out']) && $timeScanTimestamp > strtotime($date . ' ' . $existingAtt['time_in'])) {
            // Jika jam masuk sudah ada & jam pulang belum terisi, update Jam Pulang
            Database::update('attendances', [
                'time_out' => $time
            ], 'id = :id', ['id' => $existingAtt['id']]);

            $processed = true;

            // Masukkan ke Antrean WhatsApp (Pulang)
            $waQueued = $this->enqueueWhatsapp($student, $date, $time, $existingAtt['status'] ?? 'hadir', 'pulang');
        }

        return [
            'processed' => $processed,
            'wa_queued' => $waQueued
        ];
    }

    /**
     * Membuat antrean pesan WhatsApp untuk wali murid (Ayah, Ibu, Wali, WA Utama) & siswa
     */
    private function enqueueWhatsapp(array $student, string $date, string $time, string $status, string $type): bool {
        $recipients = [];

        // 1. Ambil nomor-nomor dari tabel parents
        if (!empty($student['parent_id'])) {
            $parent = Database::fetch("SELECT primary_whatsapp, father_whatsapp, mother_whatsapp, guardian_whatsapp, wa_active FROM parents WHERE id = ?", [$student['parent_id']]);
            
            // Cek apakah wa_active aktif (1/null)
            if ($parent && ($parent['wa_active'] ?? 1) == 1) {
                if (!empty($parent['father_whatsapp']))   $recipients[] = $parent['father_whatsapp'];
                if (!empty($parent['mother_whatsapp']))   $recipients[] = $parent['mother_whatsapp'];
                if (!empty($parent['guardian_whatsapp'])) $recipients[] = $parent['guardian_whatsapp'];
                if (!empty($parent['primary_whatsapp']))  $recipients[] = $parent['primary_whatsapp'];
            }
        }

        // 2. Tambahkan nomor WhatsApp siswa (jika terisi)
        if (!empty($student['whatsapp'])) {
            $recipients[] = $student['whatsapp'];
        }

        // 3. Normalisasi nomor & eliminasi duplikasi
        $normalizedPhones = [];
        foreach ($recipients as $rawPhone) {
            $cleaned = WhatsAppService::normalizePhone($rawPhone);
            // Abaikan jika tidak valid/terlalu pendek (misal '08xxx')
            if (!empty($cleaned) && strlen($cleaned) >= 10 && !in_array($cleaned, $normalizedPhones)) {
                $normalizedPhones[] = $cleaned;
            }
        }

        if (empty($normalizedPhones)) {
            return false;
        }

        // Tentukan template code
        if ($type === 'masuk') {
            $templateCode = ($status === 'terlambat') ? 'attendance_late' : 'ATTENDANCE_IN';
        } else {
            $templateCode = 'ATTENDANCE_OUT';
        }

        // Ambil nama kelas siswa
        $classRow = Database::fetch("SELECT name FROM classes WHERE id = ?", [$student['class_id']]);
        $className = $classRow['name'] ?? '-';

        // Ambil template pesan
        $template = Database::fetch("SELECT content FROM whatsapp_templates WHERE code = ? AND is_active = 1", [$templateCode]);

        if (!$template && $templateCode === 'ATTENDANCE_IN') {
            $template = Database::fetch("SELECT content FROM whatsapp_templates WHERE code = 'attendance_present' AND is_active = 1");
        } else if (!$template && $templateCode === 'ATTENDANCE_OUT') {
            $template = Database::fetch("SELECT content FROM whatsapp_templates WHERE code = 'attendance_out' AND is_active = 1");
        }

        if (!$template) {
            $message = "Info Absensi: Siswa {$student['name']} (Kelas {$className}) telah melakukan absensi {$type} pada {$date} jam {$time}. Status: " . strtoupper($status);
        } else {
            $message = str_replace(
                ['{nama_siswa}', '{kelas}', '{tanggal}', '{jam}', '{status}', '{tipe}'],
                [$student['name'], $className, date('d M Y', strtotime($date)), substr($time, 0, 5), strtoupper($status), strtoupper($type)],
                $template['content']
            );
        }

        $queuedAny = false;

        // 4. Masukkan ke whatsapp_queue untuk setiap nomor unik
        foreach ($normalizedPhones as $phone) {
            $existQueue = Database::fetch("SELECT id FROM whatsapp_queue WHERE phone = ? AND message = ? AND DATE(created_at) = ?", [
                $phone,
                $message,
                $date
            ]);

            if (!$existQueue) {
                Database::insert('whatsapp_queue', [
                    'student_id'   => $student['id'],
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