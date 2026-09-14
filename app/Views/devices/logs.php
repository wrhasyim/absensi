<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Tanggal</th><th>Jam</th><th>FP ID</th><th>Pengguna (Siswa / Guru)</th><th>Mesin</th><th>Tipe</th><th>Status</th></tr></thead>
<tbody>
<?php foreach($logs as $l): ?>
<tr>
    <td><?= e($l['log_date'] ?? substr($l['log_datetime'] ?? '', 0, 10)) ?></td>
    <td><?= e($l['log_time'] ?? substr($l['log_datetime'] ?? '', 11, 8)) ?></td>
    <td><code><?= e($l['fingerprint_id']) ?></code></td>
    <td>
        <?php if (!empty($l['user_name'])): ?>
            <?= e($l['user_name']) ?><br>
            <small class="text-muted"><?= e($l['user_role']) ?></small>
        <?php else: ?>
            <em class="text-muted">Tidak dikenal</em>
        <?php endif; ?>
    </td>
    <td><?= e($l['device_name'] ?? '-') ?></td>
    <td><span class="badge bg-secondary"><?= strtoupper($l['transaction_type'] ?? 'IN') ?></span></td>
    <td><?= e($l['status'] ?? '-') ?></td>
</tr>
<?php endforeach; if (empty($logs)): ?><tr><td colspan="7" class="text-center py-4 text-muted">Belum ada log.</td></tr><?php endif; ?>
</tbody></table></div></div>