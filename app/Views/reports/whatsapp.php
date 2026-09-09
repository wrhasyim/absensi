<div class="row g-3 mb-3">
  <div class="col"><div class="stat primary"><div class="label">Total</div><div class="value"><?= $summary['total'] ?></div></div></div>
  <div class="col"><div class="stat success"><div class="label">Terkirim</div><div class="value"><?= $summary['sent'] ?></div></div></div>
  <div class="col"><div class="stat danger"><div class="label">Gagal</div><div class="value"><?= $summary['failed'] ?></div></div></div>
  <div class="col"><div class="stat warning"><div class="label">Pending/Retry</div><div class="value"><?= $summary['pending'] ?></div></div></div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Waktu</th><th>Phone</th><th>Status</th><th>Attempt</th><th>Error</th></tr></thead>
<tbody><?php foreach($rows as $r): ?>
<tr><td><small><?= e($r['created_at']) ?></small></td><td><code><?= e($r['phone']) ?></code></td>
<td><span class="badge-status st-<?= e($r['status']) ?>"><?= strtoupper($r['status']) ?></span></td>
<td><?= $r['attempt'] ?></td><td><small class="text-danger"><?= e($r['last_error']) ?></small></td></tr>
<?php endforeach; ?></tbody></table></div></div>
