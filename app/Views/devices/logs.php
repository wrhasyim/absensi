<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Tanggal</th><th>Jam</th><th>FP ID</th><th>Siswa</th><th>Mesin</th><th>Tipe</th><th>Status</th></tr></thead>
<tbody>
<?php foreach($logs as $l): ?>
<tr><td><?= e($l['log_date']) ?></td><td><?= e($l['log_time']) ?></td><td><code><?= e($l['fingerprint_id']) ?></code></td>
<td><?= e($l['student_name'] ?? '<em>Tidak dikenal</em>') ?></td><td><?= e($l['device_name']) ?></td>
<td><span class="badge bg-secondary"><?= strtoupper($l['transaction_type']) ?></span></td><td><?= e($l['status']) ?></td></tr>
<?php endforeach; if (empty($logs)): ?><tr><td colspan="7" class="text-center py-4 text-muted">Belum ada log.</td></tr><?php endif; ?>
</tbody></table></div></div>
