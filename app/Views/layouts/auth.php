<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - <?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-brand"><i class="bi bi-fingerprint"></i></div>
    <h2>Sistem Absensi Sekolah</h2>
    <p class="muted">Fingerprint Solution X100C · WhatsApp Notifikasi</p>
    <?= $content ?>
  </div>
  <div class="auth-side">
    <div class="side-content">
      <h1>Absensi Modern, Notifikasi Cepat</h1>
      <p>Terintegrasi dengan mesin fingerprint Solution X100C dan WhatsApp Gateway untuk memberi kabar cepat kepada orang tua saat siswa tiba di sekolah.</p>
      <ul>
        <li><i class="bi bi-check2-circle"></i> Realtime monitor absensi</li>
        <li><i class="bi bi-check2-circle"></i> Notifikasi WhatsApp otomatis</li>
        <li><i class="bi bi-check2-circle"></i> Berjalan di jaringan LAN sekolah</li>
      </ul>
    </div>
  </div>
</div>
</body></html>
