<div class="card mb-3"><div class="card-body">
<form method="GET" class="row g-2 align-items-end">
  <div class="col-md-3"><label class="form-label">Tanggal</label><input type="date" name="date" value="<?= e($date) ?>" class="form-control" data-testid="attendance-date"></div>
  <div class="col-md-3"><label class="form-label">Kelas</label><select name="class_id" class="form-select" data-testid="attendance-class"><option value="">Semua</option>
    <?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
  </select></div>
  <div class="col-md-3"><button class="btn btn-primary" data-testid="attendance-filter">Filter</button> <a href="<?= url('/attendance/permit') ?>" class="btn btn-outline-primary">Input Izin/Sakit</a></div>
</form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>NIS / NIP</th><th>Nama</th><th>Kelas</th><th>Masuk</th><th>Pulang</th><th>Status</th><th>Catatan</th></tr></thead>
<tbody data-testid="attendance-body">
<?php foreach($rows as $r): ?>
<tr>
  <!-- Perbaikan fallback pengecekan identifier, nis, atau nip -->
  <td><?= e($r['identifier'] ?? $r['nis'] ?? $r['nip'] ?? '-') ?></td>
  <td>
    <?= e($r['user_name']) ?>
    <span class="badge <?= $r['role'] == 'Guru' ? 'bg-primary' : 'bg-secondary' ?>"><?= e($r['role']) ?></span>
  </td>
  <td><?= e($r['class_name'] ?? '-') ?></td>
  <td><?= e(substr($r['time_in'] ?? '',0,5)) ?: '-' ?></td>
  <td><?= e(substr($r['time_out'] ?? '',0,5)) ?: '-' ?></td>
  <td><span class="badge-status st-<?= e($r['status']) ?>"><?= strtoupper($r['status']) ?></span></td>
  <td><?= e($r['note'] ?? '-') ?></td>
</tr>
<?php endforeach; if (empty($rows)): ?><tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data.</td></tr><?php endif; ?>
</tbody></table></div></div>