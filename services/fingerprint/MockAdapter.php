<?php
namespace App\Services\Fingerprint;

/**
 * MockAdapter - untuk development/testing tanpa perangkat nyata.
 * Menghasilkan data sample yang realistis.
 */
class MockAdapter implements AdapterInterface {
    private array $config;
    private bool $connected = false;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function connect(): bool {
        $this->connected = true;
        app_log('fingerprint', "[MOCK] Connected to {$this->config['ip']}:{$this->config['port']}");
        return true;
    }

    public function disconnect(): void {
        $this->connected = false;
    }

    public function isConnected(): bool { return $this->connected; }

    public function testConnection(): array {
        return [
            'success' => true,
            'message' => 'MOCK: Simulated connection OK',
            'ip' => $this->config['ip'] ?? '',
            'port' => $this->config['port'] ?? 4370,
        ];
    }

    public function getAttendanceLogs(?string $sinceDate = null): array {
        // Fetch registered fingerprint IDs from DB
        $students = \App\Core\Database::fetchAll("SELECT fingerprint_id FROM students WHERE fingerprint_id IS NOT NULL AND status='aktif' LIMIT 15");
        $now = time();
        $today = date('Y-m-d');
        $logs = [];
        foreach ($students as $s) {
            $offset = rand(-15, 30) * 60; // -15 to +30 minutes around 07:00
            $ts = strtotime($today . ' 07:00:00') + $offset;
            if ($ts > $now) continue;
            $logs[] = [
                'fingerprint_id' => $s['fingerprint_id'],
                'timestamp' => date('Y-m-d H:i:s', $ts),
                'type' => 'in',
                'status' => 0,
                'raw' => 'MOCK',
            ];
        }
        return $logs;
    }

    public function clearAttendanceLogs(): bool { return true; }
    public function syncTime(): bool { return true; }

    public function getDeviceInfo(): array {
        return [
            'firmware' => 'MOCK v1.0',
            'serial' => 'MOCK-X100C-000',
            'user_count' => 0,
            'log_count' => 0,
        ];
    }
}
