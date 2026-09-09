<div class="card mb-3"><div class="card-body d-flex justify-content-between align-items-center">
  <div><i class="bi bi-broadcast text-success"></i> <b>Monitoring Realtime</b> — Update tiap 5 detik. Waktu: <span id="mon-time">-</span></div>
  <a href="<?= url('/devices') ?>" class="btn btn-sm btn-outline-primary">Sync Fingerprint</a>
</div></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Jam</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th><th>Mesin</th><th>WA</th></tr></thead>
<tbody id="monitor-body" data-testid="monitor-body">
<?php foreach($rows as $r): ?>
<tr>
  <td><?= e(substr($r['time_in'] ?? '', 0, 5)) ?></td>
  <td><?= e($r['nis']) ?></td>
  <td><?= e($r['student_name']) ?></td>
  <td><?= e($r['class_name']) ?></td>
  <td><span class="badge-status st-<?= e($r['status']) ?>"><?= strtoupper($r['status']) ?></span></td>
  <td><?= e($r['device_name'] ?? '-') ?></td>
  <td><?php $ws=$r['wa_status'] ?? '-'; ?><span class="badge-status st-<?= e($ws) ?>"><?= strtoupper($ws) ?></span></td>
</tr>
<?php endforeach; ?>
<?php if (empty($rows)): ?><tr><td colspan="7" class="text-center py-4 text-muted">Belum ada absensi hari ini.</td></tr><?php endif; ?>
</tbody></table></div></div>

<script>
let isSyncing = false;

// 1. Fungsi khusus untuk penarikan data dari mesin & proses absensi/WA
async function triggerAutoSync() {
  if (isSyncing) return; // Mencegah request tumpang tindih
  isSyncing = true;
  
  try {
    const deviceId = 1; // Sesuaikan dengan ID perangkat di database Anda
    
    // Tarik log dari mesin ke database
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

// 2. Fungsi untuk memperbarui tampilan tabel di web
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
      <td>${x.nis}</td>
      <td>${x.student_name}</td>
      <td>${x.class_name||''}</td>
      <td><span class="badge-status st-${x.status}">${(x.status||'').toUpperCase()}</span></td>
      <td>${x.device_name||'-'}</td>
      <td><span class="badge-status st-${x.wa_status||'-'}">${(x.wa_status||'-').toUpperCase()}</span></td>
    </tr>`).join('');
  } catch(e){}
}

// Jalankan sync mesin tiap 15 detik
setInterval(triggerAutoSync, 15000);
triggerAutoSync();

// Jalankan update tampilan tabel tiap 5 detik
setInterval(refreshMonitor, 5000);
refreshMonitor();
</script>