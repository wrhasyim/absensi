<style>
/* --- GAYA KHUSUS UNTUK CETAK (PRINT) --- */
@media print {
    /* 1. Sembunyikan elemen web yang tidak perlu dicetak */
    .no-print, .navbar, .sidebar, .btn, footer {
        display: none !important;
    }

    /* 2. Hilangkan border kotak dan lebarkan ke seluruh kertas */
    body, .main-content, .container, .container-fluid {
        width: 100% !important; margin: 0 !important; padding: 0 !important;
        background-color: #fff !important; box-shadow: none !important;
    }
    .card {
        border: none !important; box-shadow: none !important;
    }

    /* 3. Rapikan tabel agar garisnya hitam tegas */
    .table {
        width: 100% !important; border-collapse: collapse !important; margin-bottom: 20px !important;
    }
    .table th, .table td {
        border: 1px solid #000 !important; padding: 8px !important; 
        font-size: 12px !important; color: #000 !important;
    }
    .table th {
        background-color: #f2f2f2 !important; 
        -webkit-print-color-adjust: exact; 
    }
    .badge-status {
        border: none !important; color: #000 !important; background: transparent !important; font-weight: bold;
    }

    /* 4. Tampilkan elemen yang khusus untuk cetak */
    .print-only {
        display: block !important;
    }
}

/* Sembunyikan Kop & TTD di layar web biasa */
@media screen {
    .print-only {
        display: none !important;
    }
}
</style>

<!-- Form Filter (Akan disembunyikan saat dicetak) -->
<div class="card mb-3 no-print"><div class="card-body">
<form method="GET" class="d-flex gap-2 align-items-end">
  <div>
    <label class="form-label">Tanggal</label>
    <input type="date" name="date" value="<?= e($date ?? date('Y-m-d')) ?>" class="form-control">
  </div>
  <div>
    <label class="form-label">Tipe Laporan</label>
    <select name="role" class="form-select">
      <option value="siswa" <?= ($role ?? 'siswa') === 'siswa' ? 'selected' : '' ?>>Laporan Siswa</option>
      <option value="guru" <?= ($role ?? 'siswa') === 'guru' ? 'selected' : '' ?>>Laporan Guru</option>
    </select>
  </div>
  <div>
    <button class="btn btn-primary">Tampilkan</button> 
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
  </div>
</form>
</div></div>

<!-- Kartu Rekap (Akan disembunyikan saat dicetak agar rapi) -->
<div class="row g-2 mb-3 no-print">
  <div class="col"><div class="stat success"><div class="label">Hadir</div><div class="value"><?= $summary['hadir'] ?? 0 ?></div></div></div>
  <div class="col"><div class="stat warning"><div class="label">Terlambat</div><div class="value"><?= $summary['terlambat'] ?? 0 ?></div></div></div>
  <div class="col"><div class="stat info"><div class="label">Izin</div><div class="value"><?= $summary['izin'] ?? 0 ?></div></div></div>
  <div class="col"><div class="stat info"><div class="label">Sakit</div><div class="value"><?= $summary['sakit'] ?? 0 ?></div></div></div>
  <div class="col"><div class="stat danger"><div class="label">Alpa</div><div class="value"><?= $summary['alpa'] ?? 0 ?></div></div></div>
  <div class="col"><div class="stat danger"><div class="label">Belum Absen</div><div class="value"><?= $summary['belum_absen'] ?? 0 ?></div></div></div>
</div>

<!-- KOP SURAT (Hanya muncul saat dicetak) -->
<div class="print-only" style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; font-size: 18px;">SMK TARUNA KARYA MANDIRI</h3>
    <h2 style="margin: 5px 0; font-size: 22px;">LAPORAN KEHADIRAN HARIAN <?= strtoupper($role ?? 'siswa') ?></h2>
    <p style="margin: 0; font-size: 14px;">Tanggal: <?= date('d F Y', strtotime($date ?? date('Y-m-d'))) ?></p>
    <hr style="border: 1px solid #000; margin-top: 15px;">
</div>

<!-- Tabel Data -->
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead>
  <tr>
    <th><?= ($role ?? 'siswa') === 'guru' ? 'NIP' : 'NIS' ?></th>
    <th>Nama Lengkap</th>
    <th><?= ($role ?? 'siswa') === 'guru' ? 'Unit / Jabatan' : 'Kelas' ?></th>
    <th>Masuk</th>
    <th>Pulang</th>
    <th>Status</th>
  </tr>
</thead>
<tbody>
<?php if (!empty($rows)): ?>
  <?php foreach($rows as $r): ?>
  <tr>
    <td><?= e($r['nis'] ?? '-') ?></td>
    <td><?= e($r['student_name'] ?? '-') ?></td>
    <td><?= e($r['class_name'] ?? '-') ?></td>
    <td><?= e(substr($r['time_in'] ?? '', 0, 5)) ?: '-' ?></td>
    <td><?= e(substr($r['time_out'] ?? '', 0, 5)) ?: '-' ?></td>
    <td><span class="badge-status st-<?= e($r['status'] ?? '') ?>"><?= strtoupper($r['status'] ?? '') ?></span></td>
  </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr><td colspan="6" class="text-center py-4 text-muted">Data absensi tidak ditemukan pada tanggal ini.</td></tr>
<?php endif; ?>
</tbody>
</table></div></div>

<!-- TANDA TANGAN (Hanya muncul saat dicetak) -->
<div class="print-only" style="width: 100%; margin-top: 40px; page-break-inside: avoid;">
    <div style="float: right; text-align: center; width: 250px;">
        <p style="margin-bottom: 70px;">Ciamis, <?= date('d F Y', strtotime($date ?? date('Y-m-d'))) ?><br>Kepala Sekolah,</p>
        <p><strong>( .............................................. )</strong><br>NIP. ....................................</p>
    </div>
    <div style="clear: both;"></div>
</div>