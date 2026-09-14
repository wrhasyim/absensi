<style>
/* --- GAYA KHUSUS UNTUK CETAK (PRINT) --- */
@media print {
    .no-print, .navbar, .sidebar, .btn, footer {
        display: none !important;
    }
    body, .main-content, .container, .container-fluid {
        width: 100% !important; margin: 0 !important; padding: 0 !important;
        background-color: #fff !important; box-shadow: none !important;
    }
    .card {
        border: none !important; box-shadow: none !important;
    }
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
    .print-only {
        display: block !important;
    }
}
@media screen {
    .print-only {
        display: none !important;
    }
}
</style>

<!-- Form Filter (Disembunyikan saat dicetak) -->
<div class="card mb-3 no-print"><div class="card-body">
<form method="GET" class="d-flex gap-2 align-items-end">
  <div>
    <label class="form-label">Bulan</label>
    <input type="month" name="month" value="<?= e($month ?? date('Y-m')) ?>" class="form-control">
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

<!-- KOP SURAT (Hanya muncul saat dicetak) -->
<div class="print-only" style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; font-size: 18px;">SMK TARUNA KARYA MANDIRI</h3>
    <h2 style="margin: 5px 0; font-size: 22px;">REKAP KEHADIRAN BULANAN <?= strtoupper($role ?? 'siswa') ?></h2>
    <p style="margin: 0; font-size: 14px;">Bulan: <?= date('F Y', strtotime(($month ?? date('Y-m')) . '-01')) ?></p>
    <hr style="border: 1px solid #000; margin-top: 15px;">
</div>

<!-- Tabel Data -->
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead>
  <tr>
    <th><?= ($role ?? 'siswa') === 'guru' ? 'NIP' : 'NIS' ?></th>
    <th>Nama Lengkap</th>
    <th><?= ($role ?? 'siswa') === 'guru' ? 'Unit / Jabatan' : 'Kelas' ?></th>
    <th class="text-success">Hadir</th>
    <th class="text-warning">Terlambat</th>
    <th>Izin</th>
    <th>Sakit</th>
    <th class="text-danger">Alpa</th>
  </tr>
</thead>
<tbody>
<?php if (!empty($rows)): ?>
  <?php foreach($rows as $r): ?>
  <tr>
    <td><?= e($r['nis'] ?? '-') ?></td>
    <td><?= e($r['name'] ?? '-') ?></td>
    <td><?= e($r['class_name'] ?? '-') ?></td>
    <td><?= $r['hadir'] ?? 0 ?></td>
    <td><?= $r['terlambat'] ?? 0 ?></td>
    <td><?= $r['izin'] ?? 0 ?></td>
    <td><?= $r['sakit'] ?? 0 ?></td>
    <td><?= $r['alpa'] ?? 0 ?></td>
  </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr><td colspan="8" class="text-center py-4 text-muted">Data absensi tidak ditemukan pada bulan ini.</td></tr>
<?php endif; ?>
</tbody>
</table></div></div>

<!-- TANDA TANGAN (Hanya muncul saat dicetak) -->
<div class="print-only" style="width: 100%; margin-top: 40px; page-break-inside: avoid;">
    <div style="float: right; text-align: center; width: 250px;">
        <p style="margin-bottom: 70px;">Ciamis, <?= date('d F Y') ?><br>Kepala Sekolah,</p>
        <p><strong>( .............................................. )</strong><br>NIP. ....................................</p>
    </div>
    <div style="clear: both;"></div>
</div>