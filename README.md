# Sistem Absensi Sekolah — Fingerprint Solution X100C + WhatsApp Gateway

Aplikasi absensi siswa berbasis PHP Native + MySQL/MariaDB + Node.js WhatsApp Gateway, dirancang untuk berjalan pada server lokal sekolah (LAN).

## Fitur Utama
- Login role-based (Super Admin, Admin, Guru, Kepala Sekolah)
- Master Siswa, Orang Tua, Guru, Kelas, Jurusan
- Absensi otomatis dari fingerprint Solution X100C (adapter Mock + Adapter X100C)
- Monitor absensi realtime
- Antrian WhatsApp dengan retry, template pesan yang bisa diedit
- Anti-duplikasi notifikasi
- Laporan harian, bulanan, WhatsApp
- System Health page

## Requirement
- PHP 8.x + ekstensi: mysql, mbstring, curl, xml, zip, bcmath
- MySQL 5.7+ / MariaDB 10.x
- Node.js 18+
- Chromium/Chrome (untuk WhatsApp gateway)

## Instalasi (Windows / XAMPP)
```
1. Install XAMPP (Apache + PHP 8.x + MySQL/MariaDB)
2. Install Node.js LTS
3. Copy folder `absensi` ke `C:\xampp\htdocs\absensi`
4. Copy folder `whatsapp-gateway` ke `C:\absensi-whatsapp`
5. Buat database `absensi_sekolah` di phpMyAdmin
6. Salin `.env.example` menjadi `.env`, isi DB credential
7. Jalankan seeder: `php C:\xampp\htdocs\absensi\database\seeders\seed.php`
8. Buka aplikasi: http://<IP-SERVER>/absensi/public
9. Login: admin / admin123
```

## Menjalankan WhatsApp Gateway
```
cd /app/whatsapp-gateway
npm install
node src/server.js
```
Endpoint:
- `GET /api/whatsapp/status`
- `GET /api/whatsapp/qr`
- `POST /api/whatsapp/send { phone, message }`
- `POST /api/whatsapp/reconnect`
- `POST /api/whatsapp/logout`

Autentikasi dengan header `X-API-Key: <API_KEY>` (lihat `.env`).

## Konfigurasi Fingerprint Solution X100C
Aplikasi menyediakan **adapter pattern** untuk pemisahan komunikasi perangkat:
- `services/fingerprint/AdapterInterface.php` — kontrak adapter
- `services/fingerprint/MockAdapter.php` — untuk development/testing tanpa perangkat
- `services/fingerprint/SolutionX100CAdapter.php` — production adapter (skeleton TCP)

**PENTING**: Solution X100C umumnya kompatibel dengan protokol ZKTeco (TCP port 4370). Untuk pemakaian produksi, pilih salah satu:
1. Install library PHP: `composer require jmrashed/zkteco` lalu integrasikan di `SolutionX100CAdapter::getAttendanceLogs()`.
2. Gunakan SDK resmi (Windows DLL) via COM wrapper.
3. Reimplement protokol ZKTeco (TCP handshake, command packets) di adapter.

## Scheduler (Cron)
Linux:
```
* * * * * /usr/bin/php /app/absensi/cron/process_whatsapp_queue.php >> /app/absensi/storage/logs/cron.log 2>&1
*/2 * * * * /usr/bin/php /app/absensi/cron/sync_fingerprint.php >> /app/absensi/storage/logs/cron.log 2>&1
*/10 * * * * /usr/bin/php /app/absensi/cron/check_absent_students.php >> /app/absensi/storage/logs/cron.log 2>&1
```

Windows Task Scheduler: jalankan `php.exe <path\to\script>.php` tiap 1-10 menit.

## Konfigurasi Server LAN
- Aplikasi diakses via `http://192.168.1.10/absensi/public`
- Node Gateway di `http://192.168.1.10:3000`
- Mesin Solution X100C: IP static di jaringan LAN (contoh `192.168.1.201:4370`)
- Buka Windows Firewall untuk port 80, 3000, dan 4370

## Struktur Folder
```
absensi/
├── app/
│   ├── Core/           # Router, Database, Auth, Csrf, Controller
│   ├── Controllers/
│   └── Views/
├── config/             # config.php (.env loader)
├── services/
│   ├── FingerprintService.php
│   ├── fingerprint/    # adapter interface + Mock + X100C
│   └── WhatsAppService.php
├── database/
│   ├── migrations/schema.sql
│   └── seeders/seed.php
├── public/             # index.php + assets
├── storage/logs/
├── cron/
└── .env / .env.example

whatsapp-gateway/
├── src/server.js       # Express + whatsapp-web.js
├── sessions/           # LocalAuth session (persistent)
├── logs/
├── .env
└── package.json
```

## Login Awal (Demo Data)
| Role | Username | Password |
|------|----------|----------|
| Super Admin | admin | admin123 |
| Admin/Operator | operator | operator123 |
| Guru | guru1 | guru123 |
| Kepala Sekolah | kepsek | kepsek123 |

## Troubleshooting
- **PHP built-in server "Address already in use"**: ganti port di supervisord (dev) atau di Apache config (prod).
- **WhatsApp QR tidak muncul**: pastikan chromium/chrome ter-install & path benar di `whatsapp-gateway/src/server.js`.
- **Fingerprint tidak sinkron**: pastikan IP mesin dapat di-ping dari server. Coba tombol **Test** di halaman Perangkat.
