<?php use App\Core\Csrf; ?>
<div class="alert alert-info"><i class="bi bi-info-circle"></i> Adapter <b>mock</b> untuk testing tanpa perangkat nyata. Untuk produksi ubah adapter menjadi <b>x100c</b> dan sesuaikan IP mesin.
<br><b>PENTING:</b> Mesin Solution X100C berkomunikasi lewat UDP LAN. Test koneksi <b>x100c</b> hanya berhasil bila server yang menjalankan aplikasi ini berada di jaringan LAN yang sama dengan mesin (contoh: server sekolah XAMPP di <code>192.168.1.10</code> dan mesin di <code>192.168.1.201</code>). Di lingkungan preview cloud, koneksi ke IP LAN sekolah <b>tidak dapat</b> dijangkau — normal jika tes gagal di sini; jalankan aplikasi di server sekolah untuk mengujinya secara nyata.</div>

<div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center"><span>Perangkat Fingerprint</span>
<button class="btn btn-sm btn-success" onclick="resetForm();new bootstrap.Modal(document.getElementById('device-modal')).show()" data-testid="device-add"><i class="bi bi-plus-circle"></i> Tambah</button>
</div>
<div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Nama</th><th>IP:Port</th><th>Adapter</th><th>Lokasi</th><th>Status</th><th>Last Sync</th><th>Aksi</th></tr></thead>
<tbody data-testid="devices-list">
<?php foreach($devices as $d): ?>
<tr>
  <td><b><?= e($d['name']) ?></b></td>
  <td><code><?= e($d['ip_address']) ?>:<?= e($d['port']) ?></code></td>
  <td><span class="badge bg-secondary"><?= e($d['adapter']) ?></span></td>
  <td><?= e($d['location']) ?></td>
  <td><span class="badge-status st-<?= e($d['status']) ?>"><?= strtoupper($d['status']) ?></span></td>
  <td><small><?= e($d['last_sync'] ?? '-') ?></small></td>
  <td class="text-nowrap">
    <button class="btn btn-sm btn-outline-info" onclick="testDevice(<?= $d['id'] ?>)" data-testid="device-test-<?= $d['id'] ?>"><i class="bi bi-plug"></i> Test</button>
    <button class="btn btn-sm btn-outline-primary" onclick="syncDevice(<?= $d['id'] ?>)" data-testid="device-sync-<?= $d['id'] ?>"><i class="bi bi-arrow-repeat"></i> Sync</button>
    <button class="btn btn-sm btn-outline-secondary" onclick='editDev(<?= json_encode($d) ?>)'><i class="bi bi-pencil"></i></button>
    <form method="POST" action="<?= url('/devices/'.$d['id'].'/delete') ?>" data-confirm="Hapus?" class="d-inline"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
  </td>
</tr>
<?php endforeach; ?>
<?php if (empty($devices)): ?><tr><td colspan="7" class="text-center py-4 text-muted">Belum ada perangkat.</td></tr><?php endif; ?>
</tbody></table></div></div>

<div id="sync-result"></div>

<div class="modal fade" id="device-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= url('/devices/store') ?>" id="d-form">
<div class="modal-header"><h5 class="modal-title" id="modal-title">Tambah Perangkat</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body row g-3">
  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" id="d-id">
  <div class="col-md-6"><label class="form-label">Nama *</label><input required name="name" id="d-name" class="form-control" placeholder="X100C Lantai 1" data-testid="device-name"></div>
  <div class="col-md-6"><label class="form-label">Lokasi</label><input name="location" id="d-loc" class="form-control"></div>
  <div class="col-md-6"><label class="form-label">IP Address *</label><input required name="ip_address" id="d-ip" class="form-control" placeholder="192.168.1.201" data-testid="device-ip"></div>
  <div class="col-md-3"><label class="form-label">Port</label><input type="number" name="port" id="d-port" class="form-control" value="4370"></div>
  <div class="col-md-3"><label class="form-label">Comm Key</label><input name="comm_key" id="d-key" class="form-control" value="0"></div>
  <div class="col-md-6"><label class="form-label">Adapter</label>
    <select name="adapter" id="d-adapter" class="form-select"><option value="mock">Mock (dev)</option><option value="x100c">Solution X100C</option></select>
  </div>
  <div class="col-md-6"><label class="form-label">Status</label>
    <select name="is_active" id="d-active" class="form-select">
      <option value="1">Aktif</option>
      <option value="0">Nonaktif</option>
    </select>
  </div>
</div>
<div class="modal-footer"><button class="btn btn-primary" data-testid="device-save">Simpan</button></div>
</form></div></div></div>

<script>
function resetForm() {
  document.getElementById('d-form').reset();
  document.getElementById('d-id').value = '';
  document.getElementById('modal-title').textContent = 'Tambah Perangkat';
}

function editDev(d) { 
  document.getElementById('d-id').value = d.id; 
  document.getElementById('d-name').value = d.name; 
  document.getElementById('d-loc').value = d.location || ''; 
  document.getElementById('d-ip').value = d.ip_address; 
  document.getElementById('d-port').value = d.port; 
  document.getElementById('d-key').value = d.comm_key || '0'; 
  document.getElementById('d-adapter').value = d.adapter || 'mock'; 
  document.getElementById('d-active').value = d.is_active !== undefined ? d.is_active : '1';
  document.getElementById('modal-title').textContent = 'Edit Perangkat';
  new bootstrap.Modal(document.getElementById('device-modal')).show(); 
}

async function testDevice(id) { 
  document.getElementById('sync-result').innerHTML = '<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Menguji koneksi ke mesin... (max 3-5 detik)</div>';
  try {
    const r = await fetch('<?= url("/devices") ?>/' + id + '/test'); 
    const j = await r.json(); 
    let extra = '';
    if (j.info && Object.keys(j.info).length) {
      extra = '<hr class="my-2"><div class="small"><b>Info Perangkat:</b><br>' +
        Object.entries(j.info).map(([k,v]) => `${k}: <code>${v}</code>`).join('<br>') + '</div>';
    }
    document.getElementById('sync-result').innerHTML = `<div class="alert alert-${j.success ? 'success' : 'danger'}" data-testid="test-result"><b>${j.success ? 'BERHASIL' : 'GAGAL'}:</b><br>${j.message}${extra}</div>`;
  } catch(e) {
    document.getElementById('sync-result').innerHTML = `<div class="alert alert-danger" data-testid="test-result"><b>GAGAL:</b> ${e.message}</div>`;
  }
}

async function syncDevice(id) { 
  document.getElementById('sync-result').innerHTML = '<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Menarik data dari mesin & memproses ke Monitor Absensi...</div>';
  try {
    // 1. Ambil data log dari Mesin Fingerprint ke fingerprint_logs
    const r = await window.postJson('<?= url("/devices") ?>/' + id + '/sync');
    
    // 2. Otomatis pemicu konversi dari fingerprint_logs ke tabel attendances
    let processResult = '';
    try {
      const proc = await window.postJson('<?= url("/whatsapp/queue/process") ?>');
      processResult = ` | Absensi Terproses: ${proc.total || 0}`;
    } catch(e) {
      console.log('Sync absensi diproses otomatis via trigger backend');
    }

    document.getElementById('sync-result').innerHTML = `<div class="alert alert-${r.success ? 'success' : 'danger'}"><b>Sync Berhasil:</b> ${r.message || 'Data berhasil ditarik'}${processResult}</div>`;
    
    setTimeout(() => location.reload(), 1500);
  } catch(err) {
    document.getElementById('sync-result').innerHTML = `<div class="alert alert-danger"><b>Sync Gagal:</b> ${err.message || 'Terjadi kesalahan koneksi'}</div>`;
  }
}
</script>