<div class="card mb-3"><div class="card-body d-flex justify-content-between align-items-center">
  <div><i class="bi bi-broadcast text-success"></i> <b>Monitoring Realtime</b> — Update tiap 5 detik. Waktu: <span id="mon-time">-</span></div>
  <a href="<?= url('/devices') ?>" class="btn btn-sm btn-outline-primary">Sync Fingerprint</a>
</div></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Jam</th><th>NIS/NIP</th><th>Nama</th><th>Kelas</th><th>Status</th><th>Mesin</th><th>WA</th></tr></thead>
<tbody id="monitor-body" data-testid="monitor-body">
<?php foreach($rows as $r): ?>
<tr>
  <td><?= e(substr($r['time_in'] ?? '', 0, 5)) ?></td>
  <!-- Perbaikan di PHP render awal -->
  <td><?= e($r['identifier'] ?? $r['nis'] ?? $r['nip'] ?? '-') ?></td>
  <td>
    <?= e($r['user_name']) ?>
    <span class="badge <?= $r['role'] == 'Guru' ? 'bg-primary' : 'bg-secondary' ?>"><?= e($r['role']) ?></span>
  </td>
  <td><?= e($r['class_name'] ?? '-') ?></td>
  <td><span class="badge-status st-<?= e($r['status']) ?>"><?= strtoupper($r['status']) ?></span></td>
  <td><?= e($r['device_name'] ?? '-') ?></td>
  <td><?php $ws=$r['wa_status'] ?? '-'; ?><span class="badge-status st-<?= e($ws) ?>"><?= strtoupper($ws) ?></span></td>
</tr>
<?php endforeach; ?>
<?php if (empty($rows)): ?><tr><td colspan="7" class="text-center py-4 text-muted">Belum ada absensi hari ini.</td></tr><?php endif; ?>
</tbody></table></div></div>

<script>
let isSyncing = false;

// Note: Karena Anda sudah pakai PM2, fungsi auto-sync via web ini sebenarnya 
// opsional. Anda bisa membiarkannya saja atau menghapusnya nanti jika tidak butuh.
async function triggerAutoSync() {
  if (isSyncing) return;
  isSyncing = true;
  try {
    const deviceId = 1;
    if (typeof window.postJson === 'function') {
      await window.postJson('<?= url("/devices") ?>/' + deviceId + '/sync');
      await window.postJson('<?= url("/whatsapp/queue/process") ?>');
    }
  } catch(err) {
    console.log('Auto sync skip/error:', err);
  } finally {
    isSyncing = false;
  }
}

async function refreshMonitor(){
  try {
    const r = await fetch('<?= url("/attendance/monitor.json") ?>', {credentials:'same-origin'});
    const j = await r.json();
    document.getElementById('mon-time').textContent = j.time;
    const tb = document.getElementById('monitor-body');
    if (!j.data || j.data.length===0){ 
      tb.innerHTML='<tr><td colspan="7" class="text-center py-4 text-muted">Belum ada absensi hari ini.</td></tr>'; 
      return; 
    }
    tb.innerHTML = j.data.map(x=>`<tr>
      <td>${(x.time_in||'').substring(0,5)}</td>
      <!-- Perbaikan di JavaScript AJAX Render -->
      <td>${x.identifier || x.nis || x.nip || '-'}</td>
      <td>
          ${x.user_name}
          <span class="badge ${x.role == 'Guru' ? 'bg-primary' : 'bg-secondary'}">${x.role}</span>
      </td>
      <td>${x.class_name||'-'}</td>
      <td><span class="badge-status st-${x.status}">${(x.status||'').toUpperCase()}</span></td>
      <td>${x.device_name||'-'}</td>
      <td><span class="badge-status st-${x.wa_status||'-'}">${(x.wa_status||'-').toUpperCase()}</span></td>
    </tr>`).join('');
  } catch(e){}
}

setInterval(triggerAutoSync, 15000);
triggerAutoSync();
setInterval(refreshMonitor, 5000);
refreshMonitor();
</script>