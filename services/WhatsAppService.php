<?php
namespace App\Services;

use App\Core\Database;

class WhatsAppService {
    private string $gatewayUrl;
    private string $apiKey;

    public function __construct() {
        // Mengarahkan ke port 3000 sesuai service Node.js
        $this->gatewayUrl = rtrim(env('WHATSAPP_GATEWAY_URL', 'http://127.0.0.1:3000'), '/');
        $this->apiKey = env('WHATSAPP_API_KEY', '');
    }

    public static function normalizePhone(?string $phone): ?string {
        if (!$phone) return null;
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) $phone = '62' . substr($phone, 1);
        elseif (str_starts_with($phone, '8')) $phone = '62' . $phone;
        elseif (!str_starts_with($phone, '62')) $phone = '62' . ltrim($phone, '+');
        return $phone;
    }

    /**
     * Memeriksa status koneksi WhatsApp Gateway
     */
    public function status(): array {
        $r = $this->httpGet('/status.json');
        return $r ?: ['status' => 'offline', 'error' => 'Gateway tidak terhubung'];
    }

    /**
     * Mengambil QR Code dari Gateway
     */
    public function qr(): array {
        return $this->httpGet('/qr.json') ?: ['qr' => null];
    }

    /**
     * Menghubungkan ulang sesi WhatsApp
     */
    public function reconnect(): array {
        return $this->httpPost('/reconnect', []) ?: ['success' => false];
    }

    /**
     * Logout dari sesi WhatsApp
     */
    public function logout(): array {
        return $this->httpPost('/logout', []) ?: ['success' => false];
    }

    /**
     * Kirim pesan langsung ke Node.js WA Gateway
     */
    public function sendDirect(string $phone, string $message): array {
        return $this->httpPost('/send-message', [
            'phone' => $phone,
            'message' => $message,
        ]) ?: ['success' => false, 'error' => 'Gateway unreachable / Offline'];
    }

    /**
     * Render template pesan dengan variabel
     */
    public function render(string $template, array $vars): string {
        foreach ($vars as $k => $v) {
            $template = str_replace('{' . $k . '}', (string)$v, $template);
        }
        return $template;
    }

    /**
     * Antrikan pesan WhatsApp untuk kejadian absensi (digunakan jika dipanggil dari controller lain)
     */
    public function enqueueForAttendance(array $student, string $date, string $time, string $status): bool {
        $templateCode = match ($status) {
            'terlambat' => 'ATTENDANCE_IN',
            'hadir' => 'ATTENDANCE_IN',
            default => 'ATTENDANCE_IN',
        };

        $existing = Database::fetch(
            "SELECT id FROM whatsapp_queue WHERE student_id=? AND DATE(created_at)=?",
            [$student['id'], $date]
        );
        if ($existing) return false;

        $parent = null;
        if (!empty($student['parent_id'])) {
            $parent = Database::fetch("SELECT * FROM parents WHERE id=?", [$student['parent_id']]);
        }
        $phone = self::normalizePhone($parent['primary_whatsapp'] ?? $student['whatsapp'] ?? null);
        if (!$phone) return false;

        $template = Database::fetch("SELECT * FROM whatsapp_templates WHERE code=? AND is_active=1", [$templateCode]);
        if (!$template) return false;

        $classRow = Database::fetch("SELECT name FROM classes WHERE id=?", [$student['class_id']]);
        $message = $this->render($template['content'], [
            'nama_siswa' => $student['name'],
            'kelas' => $classRow['name'] ?? '-',
            'tanggal' => date('d M Y', strtotime($date)),
            'jam' => substr($time, 0, 5),
            'status' => strtoupper($status),
        ]);

        Database::insert('whatsapp_queue', [
            'student_id' => $student['id'],
            'phone' => $phone,
            'message' => $message,
            'status' => 'pending',
            'scheduled_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /**
     * Proses Queue Antrean Pesan WA
     */
    public function processQueue(int $limit = 20): array {
        $sent = 0; $failed = 0;
        $rows = Database::fetchAll(
            "SELECT * FROM whatsapp_queue WHERE status IN ('pending','retry') AND attempt < max_attempt ORDER BY id ASC LIMIT $limit"
        );
        
        foreach ($rows as $row) {
            Database::update('whatsapp_queue', ['status' => 'processing', 'attempt' => $row['attempt'] + 1], 'id=:id', ['id' => $row['id']]);
            
            $res = $this->sendDirect($row['phone'], $row['message']);
            $ok = !empty($res['success']);
            
            $newStatus = $ok ? 'sent' : (($row['attempt'] + 1) >= $row['max_attempt'] ? 'failed' : 'retry');
            
            Database::update('whatsapp_queue', [
                'status' => $newStatus,
                'last_error' => $ok ? null : ($res['error'] ?? 'Gagal terkirim'),
                'sent_at' => $ok ? date('Y-m-d H:i:s') : null,
            ], 'id=:id', ['id' => $row['id']]);

            if ($ok) $sent++; else $failed++;
        }
        
        return ['sent' => $sent, 'failed' => $failed, 'total' => count($rows)];
    }

    private function httpGet(string $path): ?array {
        return $this->request('GET', $path);
    }

    private function httpPost(string $path, array $data = []): ?array {
        return $this->request('POST', $path, $data);
    }

    private function request(string $method, string $path, array $data = []): ?array {
        $ch = curl_init($this->gatewayUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $this->apiKey,
            ],
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return null;
        }

        $decoded = json_decode($resp, true);
        return is_array($decoded) ? $decoded : null;
    }
}