<div class="card mb-3"><div class="card-body">
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