<?php
namespace App\Services\Fingerprint;

interface AdapterInterface {
    public function connect(): bool;
    public function disconnect(): void;
    public function isConnected(): bool;
    public function testConnection(): array;
    public function getAttendanceLogs(?string $sinceDate = null): array;
    public function clearAttendanceLogs(): bool;
    public function syncTime(): bool;
    public function getDeviceInfo(): array;
}
