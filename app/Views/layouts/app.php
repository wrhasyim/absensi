<?php use App\Core\Auth; use App\Core\Csrf; $u = Auth::user(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= Csrf::token() ?>">
<title><?= e($title ?? APP_NAME) ?> - <?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-icon"><i class="bi bi-fingerprint"></i></div>
      <div>
        <div class="brand-title">Absensi Sekolah</div>
        <div class="brand-sub">Fingerprint + WhatsApp</div>
      </div>
    </div>
    <nav class="nav-menu">
      <a href="<?= url('/dashboard') ?>" data-testid="nav-dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <div class="nav-group">Master Data</div>
      <a href="<?= url('/students') ?>" data-testid="nav-students"><i class="bi bi-people"></i> Data Siswa</a>
      <a href="<?= url('/parents') ?>" data-testid="nav-parents"><i class="bi bi-person-hearts"></i> Data Orang Tua</a>
      <a href="<?= url('/teachers') ?>" data-testid="nav-teachers"><i class="bi bi-person-workspace"></i> Data Guru</a>
      <a href="<?= url('/classes') ?>" data-testid="nav-classes"><i class="bi bi-building"></i> Data Kelas</a>
      <a href="<?= url('/majors') ?>" data-testid="nav-majors"><i class="bi bi-bookmark-star"></i> Data Jurusan</a>
      <div class="nav-group">Absensi</div>
      <a href="<?= url('/attendance/monitor') ?>" data-testid="nav-monitor"><i class="bi bi-broadcast"></i> Monitor Absensi</a>
      <a href="<?= url('/attendance') ?>" data-testid="nav-attendance"><i class="bi bi-calendar-check"></i> Data Absensi</a>
      <a href="<?= url('/attendance/permit') ?>" data-testid="nav-permit"><i class="bi bi-file-medical"></i> Izin &amp; Sakit</a>
      <div class="nav-group">Fingerprint</div>
      <a href="<?= url('/devices') ?>" data-testid="nav-devices"><i class="bi bi-hdd-network"></i> Perangkat</a>
      <a href="<?= url('/fingerprint/logs') ?>" data-testid="nav-fp-logs"><i class="bi bi-journal-text"></i> Log Fingerprint</a>
      <div class="nav-group">WhatsApp</div>
      <a href="<?= url('/whatsapp') ?>" data-testid="nav-wa"><i class="bi bi-whatsapp"></i> Gateway</a>
      <a href="<?= url('/whatsapp/templates') ?>" data-testid="nav-wa-tpl"><i class="bi bi-chat-square-text"></i> Template Pesan</a>
      <a href="<?= url('/whatsapp/queue') ?>" data-testid="nav-wa-queue"><i class="bi bi-inbox"></i> Queue Pesan</a>
      <div class="nav-group">Laporan</div>
      <a href="<?= url('/reports/daily') ?>" data-testid="nav-report-daily"><i class="bi bi-file-earmark-text"></i> Harian</a>
      <a href="<?= url('/reports/monthly') ?>" data-testid="nav-report-monthly"><i class="bi bi-file-earmark-bar-graph"></i> Bulanan</a>
      <a href="<?= url('/reports/whatsapp') ?>" data-testid="nav-report-wa"><i class="bi bi-envelope-check"></i> WhatsApp</a>
      <div class="nav-group">Sistem</div>
      <a href="<?= url('/settings') ?>" data-testid="nav-settings"><i class="bi bi-gear"></i> Pengaturan</a>
      <a href="<?= url('/system/health') ?>" data-testid="nav-health"><i class="bi bi-activity"></i> System Health</a>
    </nav>
  </aside>
  <main class="main">
    <header class="topbar">
      <button class="btn btn-sm btn-icon sidebar-toggle" onclick="document.body.classList.toggle('sidebar-open')" data-testid="sidebar-toggle"><i class="bi bi-list"></i></button>
      <h1 class="page-title"><?= e($title ?? '') ?></h1>
      <div class="user-menu">
        <span class="badge role-badge"><?= e(strtoupper($u['role'] ?? '')) ?></span>
        <span class="user-name"><i class="bi bi-person-circle"></i> <?= e($u['name'] ?? 'User') ?></span>
        <form method="POST" action="<?= url('/logout') ?>" style="display:inline">
          <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
          <button class="btn btn-sm btn-outline-danger" type="submit" data-testid="logout-btn"><i class="bi bi-box-arrow-right"></i> Keluar</button>
        </form>
      </div>
    </header>
    <?php if ($m = flash('success')): ?><div class="alert alert-success" data-testid="flash-success"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash('error')): ?><div class="alert alert-danger" data-testid="flash-error"><?= e($m) ?></div><?php endif; ?>
    <div class="content"><?= $content ?></div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>window.APP_BASE = '<?= url('') ?>'; window.CSRF = '<?= Csrf::token() ?>';</script>
<script src="<?= asset('js/app.js') ?>"></script>
</body></html>
