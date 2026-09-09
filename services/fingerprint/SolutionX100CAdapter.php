<?php
namespace App\Services\Fingerprint;

use Jmrashed\Zkteco\Lib\ZKTeco;

/**
 * SolutionX100CAdapter - production adapter untuk mesin Solution X100C
 * menggunakan library ZKTeco (jmrashed/zkteco).
 *
 * Solution X100C kompatibel dengan protokol ZKTeco standar (UDP port 4370).
 * Library ini menangani seluruh command packet & parsing response.
 *
 * Konfigurasi:
 *   - ip:   IP LAN mesin (mis. 192.168.1.201)
 *   - port: 4370 (default ZKTeco / X100C)
 *   - timeout_seconds: default 3 detik (dipakai untuk test connection)
 *
 * Catatan:
 *  - Komunikasi menggunakan UDP, bukan TCP. Tidak ada handshake TCP.
 *  - Library default menunggu balasan UDP hingga 60 detik. Kelas ini
 *    memaksa timeout pendek (3 detik) untuk operasi ringan (connect/test/info)
 *    dan timeout lebih panjang (15 detik) untuk operasi getAttendance.
 */
class SolutionX100CAdapter implements AdapterInterface {
    private array $config;
    private ?ZKTeco $zk = null;
    private bool $connected = false;
    private int $shortTimeout = 3;   // detik - untuk test/connect/info
    private int $longTimeout  = 15;  // detik - untuk ambil log

    public function __construct(array $config) {
        $this->config = $config;
    }

    private function device(?int $timeoutSeconds = null): ZKTeco {
        if ($this->zk === null) {
            $this->zk = new ZKTeco(
                $this->config['ip'] ?? '',
                (int)($this->config['port'] ?? 4370)
            );
        }
        // Override the library's UDP receive timeout (default is 60s)
        if ($timeoutSeconds !== null && isset($this->zk->_zkclient) && is_resource($this->zk->_zkclient) === false && $this->zk->_zkclient !== false) {
            @socket_set_option($this->zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeoutSeconds, 'usec' => 0]);
        }
        return $this->zk;
    }

    /**
     * Setel timeout UDP receive pada socket library.
     */
    private function setSocketTimeout(int $seconds): void {
        if ($this->zk && !empty($this->zk->_zkclient)) {
            @socket_set_option($this->zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $seconds, 'usec' => 0]);
        }
    }

    public function connect(): bool {
        $ip = $this->config['ip'] ?? '';
        $port = (int)($this->config['port'] ?? 4370);
        if (!$ip) {
            $this->connected = false;
            return false;
        }
        try {
            $this->device();                 // create socket
            $this->setSocketTimeout($this->shortTimeout);
            $this->connected = (bool) $this->zk->connect();
            app_log('fingerprint', "[X100C] Connect " . ($this->connected ? 'OK' : 'FAIL(no response)') . " $ip:$port");
            return $this->connected;
        } catch (\Throwable $e) {
            app_log('fingerprint', "[X100C] Connect exception: " . $e->getMessage());
            $this->connected = false;
            return false;
        }
    }

    public function disconnect(): void {
        try {
            if ($this->zk && $this->connected) {
                @$this->zk->disconnect();
            }
        } catch (\Throwable $e) {}
        $this->connected = false;
    }

    public function isConnected(): bool { return $this->connected; }

    public function testConnection(): array {
        $ip = $this->config['ip'] ?? '';
        $port = (int)($this->config['port'] ?? 4370);

        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return [
                'success' => false,
                'message' => "IP address tidak valid: '$ip'. Isi IP mesin yang sesuai (contoh 192.168.1.201).",
                'ip' => $ip, 'port' => $port,
            ];
        }

        // Pra-cek: apakah PHP extension sockets tersedia?
        if (!function_exists('socket_create')) {
            return [
                'success' => false,
                'message' => "PHP extension 'sockets' belum aktif. Install: apt-get install php-sockets (Linux) atau aktifkan php_sockets di php.ini (Windows).",
                'ip' => $ip, 'port' => $port,
            ];
        }

        // Pra-cek: apakah IP tampak sebagai LAN address?
        $isLAN = self::isLanIp($ip);
        $lanNote = $isLAN ? null : "Peringatan: $ip bukan IP LAN privat. Pastikan server ini berada di jaringan yang sama dengan mesin fingerprint.";

        $t0 = microtime(true);
        $ok = $this->connect();
        $elapsed = round(microtime(true) - $t0, 2);

        $info = [];
        if ($ok) {
            try {
                $info['firmware']    = @$this->zk->version();
                $info['serial']      = @$this->zk->serialNumber();
                $info['name']        = @$this->zk->deviceName();
                $info['device_time'] = @$this->zk->getTime();
            } catch (\Throwable $e) {}
        }
        $this->disconnect();

        if ($ok) {
            return [
                'success' => true,
                'message' => "Terhubung ke Solution X100C dalam {$elapsed}s. Firmware: " . ($info['firmware'] ?? '?'),
                'ip' => $ip, 'port' => $port, 'info' => $info,
            ];
        }

        // Diagnostic message ketika gagal
        $reasons = [
            "Mesin dengan IP <b>$ip:$port</b> tidak merespon dalam {$this->shortTimeout} detik (elapsed {$elapsed}s).",
            "Kemungkinan penyebab:",
            "&nbsp;&nbsp;• Server ini tidak berada di jaringan LAN yang sama dengan mesin (contoh: preview cloud tidak bisa menjangkau IP LAN sekolah).",
            "&nbsp;&nbsp;• Mesin fingerprint dalam keadaan mati atau kabel LAN lepas.",
            "&nbsp;&nbsp;• IP/port salah — cek di menu <i>Comm</i> mesin (default 4370).",
            "&nbsp;&nbsp;• Firewall memblokir UDP port $port.",
        ];
        if ($lanNote) $reasons[] = "&nbsp;&nbsp;• $lanNote";

        return [
            'success' => false,
            'message' => implode('<br>', $reasons),
            'ip' => $ip, 'port' => $port,
            'elapsed' => $elapsed,
        ];
    }

    /**
     * Deteksi apakah IP termasuk range LAN privat (RFC1918) / link-local.
     */
    public static function isLanIp(string $ip): bool {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
        $long = ip2long($ip);
        if ($long === false) return false;
        // 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 169.254.0.0/16
        return ($long >= ip2long('10.0.0.0')      && $long <= ip2long('10.255.255.255'))
            || ($long >= ip2long('172.16.0.0')    && $long <= ip2long('172.31.255.255'))
            || ($long >= ip2long('192.168.0.0')   && $long <= ip2long('192.168.255.255'))
            || ($long >= ip2long('169.254.0.0')   && $long <= ip2long('169.254.255.255'));
    }

    /**
     * Ambil semua log absensi dari mesin dan konversi ke format standar internal:
     *   [ ['fingerprint_id'=>..., 'timestamp'=>YYYY-MM-DD HH:MM:SS, 'type'=>'in|out', 'raw'=>...] ]
     */
    public function getAttendanceLogs(?string $sinceDate = null): array {
        if (!$this->connected && !$this->connect()) return [];
        // Timeout lebih panjang untuk data besar
        $this->setSocketTimeout($this->longTimeout);
        try {
            $raw = $this->zk->getAttendance();
            if (!is_array($raw)) return [];
            $out = [];
            foreach ($raw as $rec) {
                $fpId = (string)($rec['id'] ?? $rec['uid'] ?? '');
                $ts = $rec['timestamp'] ?? null;
                if (!$fpId || !$ts) continue;
                if ($sinceDate && strtotime($ts) < strtotime($sinceDate)) continue;
                $state = (int)($rec['state'] ?? $rec['type'] ?? 0);
                $type = ($state === 1 || $state === 5) ? 'out' : 'in';
                $out[] = [
                    'fingerprint_id' => $fpId,
                    'timestamp' => $ts,
                    'type' => $type,
                    'status' => $state,
                    'raw' => json_encode($rec),
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            app_log('fingerprint', "[X100C] getAttendanceLogs err: " . $e->getMessage());
            return [];
        }
    }

    public function clearAttendanceLogs(): bool {
        if (!$this->connected && !$this->connect()) return false;
        try {
            $this->setSocketTimeout($this->shortTimeout);
            $this->zk->clearAttendance();
            app_log('fingerprint', "[X100C] Attendance cleared");
            return true;
        } catch (\Throwable $e) {
            app_log('fingerprint', "[X100C] clearAttendance err: " . $e->getMessage());
            return false;
        }
    }

    public function syncTime(): bool {
        if (!$this->connected && !$this->connect()) return false;
        try {
            $this->setSocketTimeout($this->shortTimeout);
            $this->zk->setTime(date('Y-m-d H:i:s'));
            app_log('fingerprint', "[X100C] Time synced to " . date('Y-m-d H:i:s'));
            return true;
        } catch (\Throwable $e) {
            app_log('fingerprint', "[X100C] setTime err: " . $e->getMessage());
            return false;
        }
    }

    public function getDeviceInfo(): array {
        if (!$this->connected && !$this->connect()) return ['error' => 'offline'];
        try {
            $this->setSocketTimeout($this->shortTimeout);
            return [
                'firmware' => @$this->zk->version(),
                'serial'   => @$this->zk->serialNumber(),
                'name'     => @$this->zk->deviceName(),
                'platform' => @$this->zk->platform(),
                'device_time' => @$this->zk->getTime(),
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
