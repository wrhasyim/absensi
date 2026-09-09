<?php use App\Core\Csrf; ?>
<div class="row g-3">
<div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-whatsapp"></i> Status Gateway</div><div class="card-body">
<div class="text-center mb-3"><span class="badge-status st-<?= $status['status']=='connected'?'sent':($status['status']=='qr_required'?'processing':'failed') ?>" style="font-size:14px;padding:8px 14px" data-testid="wa-status"><?= strtoupper($status['status'] ?? 'unknown') ?></span></div>
<div id="qr-container" class="text-center mb-3"></div>
<button class="btn btn-outline-primary w-100 mb-2" onclick="refreshQR()" data-testid="wa-refresh-qr"><i class="bi bi-qr-code"></i> Refresh QR / Status</button>
<button class="btn btn-outline-warning w-100 mb-2" onclick="reconnect()" data-testid="wa-reconnect"><i class="bi bi-arrow-clockwise"></i> Reconnect</button>
<hr><h6 class="mb-2">Test Kirim</h6>
<form onsubmit="testSend(event)">
  <input id="tt-phone" placeholder="08xxx / 62xxx" class="form-control mb-2" data-testid="wa-test-phone" required>
  <textarea id="tt-msg" placeholder="Pesan test" class="form-control mb-2" rows="2" data-testid="wa-test-msg" required>Test dari Sistem Absensi.</textarea>
  <button class="btn btn-primary w-100" data-testid="wa-test-send">Kirim Test</button>
</form>
<div id="test-result" class="mt-2"></div>
</div></div></div>
<div class="col-md-8">
<div class="row g-3 mb-3">
  <div class="col"><div class="stat primary"><div class="label">Pending</div><div class="value" data-testid="wa-pending"><?= $stats['pending'] ?></div></div></div>
  <div class="col"><div class="stat info"><div class="label">Processing</div><div class="value"><?= $stats['processing'] ?></div></div></div>
  <div class="col"><div class="stat success"><div class="label">Terkirim</div><div class="value"><?= $stats['sent'] ?></div></div></div>
  <div class="col"><div class="stat danger"><div class="label">Gagal</div><div class="value"><?= $stats['failed'] ?></div></div></div>
  <div class="col"><div class="stat warning"><div class="label">Retry</div><div class="value"><?= $stats['retry'] ?></div></div></div>
</div>
<div class="card"><div class="card-header d-flex justify-content-between"><span>Antrian Terbaru</span>
<button class="btn btn-sm btn-primary" onclick="processQ()" data-testid="wa-process-queue"><i class="bi bi-play-circle"></i> Proses Queue Sekarang</button>
</div>
<div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Waktu</th><th>Phone</th><th>Pesan</th><th>Status</th></tr></thead>
<tbody>
<?php foreach($recent as $r): ?>
<tr><td><small><?= e($r['created_at']) ?></small></td><td><code><?= e($r['phone']) ?></code></td>
<td><small><?= e(mb_substr($r['message'],0,60)) ?>...</small></td>
<td><span class="badge-status st-<?= e($r['status']) ?>"><?= strtoupper($r['status']) ?></span></td></tr>
<?php endforeach; if (empty($recent)): ?><tr><td colspan="4" class="text-center py-4 text-muted">Antrian kosong.</td></tr><?php endif; ?>
</tbody></table></div></div>
</div></div>

<script>
// Endpoint langsung mengarah ke Server Node.js
const WA_NODE_URL = 'http://localhost:3000';

async function refreshQR() {
  const box = document.getElementById('qr-container');
  const statusElem = document.querySelector('[data-testid=wa-status]');

  try {
    const resStatus = await fetch(`${WA_NODE_URL}/status.json`);
    if (!resStatus.ok) throw new Error('Gateway tidak merespons');
    const s = await resStatus.json();

    const currentStatus = (s.status || 'offline').toLowerCase();
    statusElem.textContent = currentStatus.toUpperCase();

    // Perbarui badge warna status
    statusElem.className = 'badge-status ' + (
      currentStatus === 'connected' ? 'st-sent' :
      (currentStatus === 'qr_required' ? 'st-processing' : 'st-failed')
    );

    if (currentStatus === 'connected') {
      box.innerHTML = '<div class="text-success my-3"><i class="bi bi-check-circle-fill" style="font-size:48px"></i><div class="fw-bold mt-1">Terhubung</div></div>';
      return;
    }

    const resQr = await fetch(`${WA_NODE_URL}/qr.json`);
    const q = await resQr.json();

    if (q.qr) {
      box.innerHTML = `<img src="${q.qr}" style="max-width:200px;background:#fff;padding:8px;border-radius:8px;border:1px solid #ddd"><div class="small text-muted mt-2">Scan QR ini menggunakan WhatsApp di HP Anda</div>`;
    } else {
      box.innerHTML = '<div class="text-muted small my-3">QR Code belum tersedia. Klik <b>Reconnect</b> atau tunggu sebentar.</div>';
    }
  } catch (err) {
    statusElem.textContent = 'OFFLINE';
    statusElem.className = 'badge-status st-failed';
    box.innerHTML = '<div class="text-danger small my-3">Gagal terhubung ke service Node.js (Port 3000). Pastikan terminal <code>node index.js</code> aktif.</div>';
  }
}

async function reconnect() {
  const box = document.getElementById('qr-container');
  box.innerHTML = '<div class="text-info small my-3"><span class="spinner-border spinner-border-sm me-1"></span> Menghubungkan ulang...</div>';
  try {
    await fetch(`${WA_NODE_URL}/reconnect`, { method: 'POST' });
  } catch (e) {
    console.error(e);
  }
  setTimeout(refreshQR, 2000);
}

async function testSend(e) {
  e.preventDefault();
  const phone = document.getElementById('tt-phone').value;
  const message = document.getElementById('tt-msg').value;
  const resDiv = document.getElementById('test-result');

  resDiv.innerHTML = '<div class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span> Mengirim pesan...</div>';

  try {
    const response = await fetch(`${WA_NODE_URL}/send-message`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone, message })
    });

    const r = await response.json();
    resDiv.innerHTML = `<div class="alert alert-${r.success ? 'success' : 'danger'} py-2 small mt-2">${r.success ? 'Pesan berhasil dikirim!' : (r.error || 'Gagal mengirim pesan')}</div>`;
  } catch (err) {
    resDiv.innerHTML = `<div class="alert alert-danger py-2 small mt-2">Error: Service WA Gateway offline / Port 3000 tidak merespons</div>`;
  }
}

async function processQ() {
  try {
    const r = await window.postJson('<?= url("/whatsapp/queue/process") ?>');
    alert(`Diproses: ${r.total || 0} | Terkirim: ${r.sent || 0} | Gagal: ${r.failed || 0}`);
    location.reload();
  } catch (err) {
    alert('Gagal memproses antrian.');
  }
}

// Cek otomatis setiap 5 detik
setInterval(refreshQR, 5000);
refreshQR();
</script>