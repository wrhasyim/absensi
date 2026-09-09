<div class="card mb-3"><div class="card-body">
<form method="GET" class="d-flex gap-2 align-items-end"><div><label class="form-label">Tanggal</label><input type="date" name="date" value="<?= e($date) ?>" class="form-control"></div>
<div><button class="btn btn-primary">Tampilkan</button> <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button></div></form>
</div></div>
<div class="row g-2 mb-3">
  <div class="col"><div class="stat success"><div class="label">Hadir</div><div class="value"><?= $summary['hadir'] ?></div></div></div>
  <div class="col"><div class="stat warning"><div class="label">Terlambat</div><div class="value"><?= $summary['terlambat'] ?></div></div></div>
  <div class="col"><div class="stat info"><div class="label">Izin</div><div class="value"><?= $summary['izin'] ?></div></div></div>
  <div class="col"><div class="stat info"><div class="label">Sakit</div><div class="value"><?= $summary['sakit'] ?></div></div></div>
  <div class="col"><div class="stat danger"><div class="label">Alpa</div><div class="value"><?= $summary['alpa'] ?></div></div></div>
  <div class="col"><div class="stat danger"><div class="label">Belum Absen</div><div class="value"><?= $summary['belum_absen'] ?></div></div></div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Masuk</th><th>Pulang</th><th>Status</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr><td><?= e($r['nis']) ?></td><td><?= e($r['student_name']) ?></td><td><?= e($r['class_name']) ?></td>
<td><?= e(substr($r['time_in'] ?? '',0,5)) ?: '-' ?></td><td><?= e(substr($r['time_out'] ?? '',0,5)) ?: '-' ?></td>
<td><span class="badge-status st-<?= e($r['status']) ?>"><?= strtoupper($r['status']) ?></span></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
