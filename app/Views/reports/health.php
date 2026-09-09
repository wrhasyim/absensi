<div class="row g-3">
<div class="col-md-6"><div class="card"><div class="card-header">Service Status</div><div class="card-body">
  <div class="d-flex justify-content-between align-items-center mb-2"><span>PHP</span><span class="badge-status st-sent">ONLINE</span></div>
  <div class="d-flex justify-content-between align-items-center mb-2"><span>Database MySQL</span><span class="badge-status <?= $db=='ONLINE'?'st-sent':'st-failed' ?>"><?= $db ?></span></div>
  <div class="d-flex justify-content-between align-items-center mb-2"><span>WhatsApp Gateway</span><span class="badge-status <?= ($wa['status']??'')=='connected'?'st-sent':(($wa['status']??'')=='qr_required'?'st-processing':'st-failed') ?>"><?= strtoupper($wa['status'] ?? 'OFFLINE') ?></span></div>
  <hr>
  <div class="d-flex justify-content-between mb-1"><span>Queue Pending/Retry/Processing</span><b data-testid="health-queue"><?= $waQ ?></b></div>
  <div class="d-flex justify-content-between"><span>WhatsApp Failed</span><b class="text-danger"><?= $waFailed ?></b></div>
</div></div></div>
<div class="col-md-6"><div class="card"><div class="card-header">Perangkat Fingerprint</div><div class="card-body">
<?php if (empty($devices)): ?><div class="text-muted">Belum ada perangkat.</div>
<?php else: foreach($devices as $d): ?>
<div class="d-flex justify-content-between align-items-center mb-2"><div><b><?= e($d['name']) ?></b><br><small class="text-muted"><?= e($d['ip_address']) ?>:<?= e($d['port']) ?> · <?= e($d['adapter']) ?></small></div>
<span class="badge-status st-<?= e($d['status']) ?>"><?= strtoupper($d['status']) ?></span></div>
<?php endforeach; endif; ?>
</div></div></div>
</div>
