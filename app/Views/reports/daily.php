<style>
/* --- GAYA KHUSUS UNTUK CETAK (PRINT) POLOSAN --- */
@media print {
    .no-print, .navbar, .sidebar, .btn, footer, header {
        display: none !important;
    }
    body, .main-content, .container, .container-fluid, .card, .card-body {
        background-color: #ffffff !important;
        color: #000000 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
    }
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-bottom: 20px !important;
        background-color: #ffffff !important;
    }
    .table th, .table td {
        border: 1px solid #000000 !important;
        padding: 8px !important; 
        color: #000000 !important;
        background-color: #ffffff !important;
        /* Rata Tengah Vertikal dan Horizontal */
        vertical-align: middle !important; 
        text-align: center !important;     
    }
    /* Pengecualian: Kolom ke-2 (Nama Lengkap) tetap Rata Kiri */
    .table th:nth-child(2), 
    .table td:nth-child(2) {
        text-align: left !important;
    }
    .print-only {
        display: block !important;
        color: #000000 !important;
    }
}

@media screen {
    .print-only {
        display: none !important;
    }
}
</style>

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
    <hr style="border-top: 1px solid #000; margin-top: 15px;">
</div>

<div class="card"><div class="table-responsive"><table class="table mb-0 align-middle">
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
    <td><?= strtoupper($r['status'] ?? '') ?></td>
  </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr><td colspan="6" class="text-center py-4">Data absensi tidak ditemukan pada tanggal ini.</td></tr>
<?php endif; ?>
</tbody>
</table></div></div>

<!-- TANDA TANGAN DI KARAWANG -->
<div class="print-only" style="width: 100%; margin-top: 10px; page-break-inside: avoid;">
    <div style="float: right; text-align: center; width: 250px;">
        <p style="margin-bottom: 70px;">Karawang, <?= date('d F Y', strtotime($date ?? date('Y-m-d'))) ?><br>Kepala Sekolah,</p>
        <p><strong>( .............................................. )</strong><br>NIP. ....................................</p>
    </div>
    <div style="clear: both;"></div>
</div>