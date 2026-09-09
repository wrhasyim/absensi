<?php
/**
 * Cron: Proses queue WhatsApp yang pending/retry.
 * Jalankan tiap 1 menit:
 *   Linux:   * * * * * /usr/bin/php /app/absensi/cron/process_whatsapp_queue.php
 *   Windows: Task Scheduler → jalankan php.exe C:\absensi\cron\process_whatsapp_queue.php tiap 1 menit
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/WhatsAppService.php';

$svc = new \App\Services\WhatsAppService();
$res = $svc->processQueue(50);
echo date('Y-m-d H:i:s') . " | processed={$res['total']} sent={$res['sent']} failed={$res['failed']}\n";
