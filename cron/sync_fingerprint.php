<?php
/**
 * Cron: Sinkronisasi log fingerprint dari semua perangkat aktif.
 * Jalankan tiap 1-5 menit.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/FingerprintService.php';
use App\Core\Database;

$devices = Database::fetchAll("SELECT * FROM devices WHERE is_active=1");
foreach ($devices as $d) {
    $svc = new \App\Services\FingerprintService($d);
    $res = $svc->synchronize();
    echo date('Y-m-d H:i:s') . " | device={$d['name']} " . json_encode($res) . "\n";
}
