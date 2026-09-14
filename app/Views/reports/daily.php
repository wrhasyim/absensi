<div class="card mb-3"><div class="card-body">
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

<div class="row g-2 mb-3">
  <div class="col"><div class="stat success"><div class="label">Hadir</div><div class="value"><?= $summary['hadir'] ?? 0 ?></div></div></div>
  <div class="col"><div class="stat warning"><div class="label">Terlambat</div><div class="value"><?= $summary['terlambat'] ?? 0 ?></div></div></div>
  <div class="col"><div class="stat info"><div class="label">Izin</div><div class="value"><?= $summary['izin'] ?? 0 ?></div></div></div>
  <div class="col"><div class="stat info"><div class="label">Sakit</div><div class="value"><?= $summary['sakit'] ?? 0 ?></div></div></div>
  <div class="col"><div class="stat danger"><div class="label">Alpa</div><div class="value"><?= $summary['alpa'] ?? 0 ?></div></div></div>
  <div class="col"><div class="stat danger"><div class="label">Belum Absen</div><div class="value"><?= $summary['belum_absen'] ?? 0 ?></div></div></div>
</div>

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