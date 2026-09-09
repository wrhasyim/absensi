<?php use App\Core\Csrf; ?>
<div class="card mb-3"><div class="card-body">
<form method="GET" class="d-flex gap-2 align-items-end">
  <div><label class="form-label">Filter Status</label><select name="status" class="form-select"><option value="">Semua</option>
    <?php foreach(['pending','processing','sent','failed','retry'] as $s): ?><option <?= $status==$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
  </select></div>
  <div><button class="btn btn-primary">Filter</button></div>
</form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>#</th><th>Waktu</th><th>Siswa</th><th>Phone</th><th>Pesan</th><th>Status</th><th>Attempt</th><th></th></tr></thead>
<tbody data-testid="queue-body">
<?php foreach($rows as $r): ?>
<tr><td><?= $r['id'] ?></td><td><small><?= e($r['created_at']) ?></small></td><td><?= e($r['student_name'] ?? '-') ?></td>
<td><code><?= e($r['phone']) ?></code></td><td><small><?= e(mb_substr($r['message'],0,80)) ?>...</small></td>
<td><span class="badge-status st-<?= e($r['status']) ?>"><?= strtoupper($r['status']) ?></span></td>
<td><?= $r['attempt'] ?>/<?= $r['max_attempt'] ?></td>
<td>
<?php if ($r['status'] !== 'sent'): ?>
<form method="POST" action="<?= url('/whatsapp/queue/'.$r['id'].'/resend') ?>" class="d-inline"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><button class="btn btn-sm btn-outline-primary" data-testid="wa-resend-<?= $r['id'] ?>"><i class="bi bi-send"></i> Kirim Ulang</button></form>
<?php endif; ?>
</td></tr>
<?php endforeach; if (empty($rows)): ?><tr><td colspan="8" class="text-center py-4 text-muted">Kosong.</td></tr><?php endif; ?>
</tbody></table></div></div>
