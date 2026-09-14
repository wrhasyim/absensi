<?php use App\Core\Csrf; ?>
<div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center"><span>Data Guru</span>
<button class="btn btn-sm btn-success" data-testid="teacher-add" onclick="document.getElementById('t-form').reset();document.getElementById('t-id').value='';new bootstrap.Modal(document.getElementById('teacher-modal')).show()"><i class="bi bi-plus-circle"></i> Tambah</button>
</div><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>NIP / FP</th><th>Nama</th><th>Gender</th><th>WhatsApp</th><th>Target Pimpinan</th><th></th></tr></thead>
<tbody data-testid="teachers-list">
<?php foreach($teachers as $t): ?>
<tr>
  <td>
    <?= e($t['nip']) ?><br>
    <small class="text-muted">FP ID: <?= e($t['fingerprint_id'] ?: '-') ?></small>
  </td>
  <td><?= e($t['name']) ?></td>
  <td><?= $t['gender']=='L'?'L':'P' ?></td>
  <td><?= e($t['whatsapp']) ?></td>
  <td><?= e($t['kepsek_name'] ?? '-') ?></td>
  <td class="text-nowrap">
    <button class="btn btn-sm btn-outline-primary" onclick='editTeacher(<?= json_encode($t) ?>)'><i class="bi bi-pencil"></i></button>
    <form method="POST" action="<?= url('/teachers/'.$t['id'].'/delete') ?>" data-confirm="Hapus?" class="d-inline"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><button class="btn btn-sm btn-outline-danger" data-testid="teacher-delete-<?= (int)$t['id'] ?>"><i class="bi bi-trash"></i></button></form>
  </td>
</tr>
<?php endforeach; if (empty($teachers)): ?><tr><td colspan="6" class="text-center py-4 text-muted">Belum ada guru.</td></tr><?php endif; ?>
</tbody></table></div></div>

<div class="modal fade" id="teacher-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="<?= url('/teachers/store') ?>" id="t-form">
<div class="modal-header"><h5 class="modal-title">Data Guru</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body row g-3">
  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" id="t-id">
  <div class="col-md-6"><label class="form-label">NIP</label><input name="nip" id="t-nip" class="form-control"></div>
  <div class="col-md-6"><label class="form-label">Nama *</label><input required name="name" id="t-name" class="form-control" data-testid="teacher-name"></div>
  <div class="col-md-3"><label class="form-label">Gender</label><select name="gender" id="t-gender" class="form-select"><option value="L">L</option><option value="P">P</option></select></div>
  <div class="col-md-4"><label class="form-label">HP</label><input name="phone" id="t-phone" class="form-control"></div>
  <div class="col-md-5"><label class="form-label">WhatsApp</label><input name="whatsapp" id="t-wa" class="form-control"></div>
  <div class="col-md-6"><label class="form-label">Email</label><input name="email" id="t-email" class="form-control"></div>
  <div class="col-md-6"><label class="form-label">Alamat</label><input name="address" id="t-addr" class="form-control"></div>
  
  <div class="col-md-6">
    <label class="form-label">ID Fingerprint (Mesin)</label>
    <input name="fingerprint_id" id="t-fingerprint" class="form-control" placeholder="Cth: 105">
  </div>
  <div class="col-md-6">
    <label class="form-label">Pimpinan (Notifikasi WA)</label>
    <select name="leader_id" id="t-leader" class="form-select">
      <option value="">- Tanpa Notifikasi -</option>
      <?php if (!empty($leaders)): foreach($leaders as $l): ?>
        <option value="<?= $l['id'] ?>"><?= e($l['kepsek_name']) ?></option>
      <?php endforeach; endif; ?>
    </select>
  </div>

</div>
<div class="modal-footer"><button class="btn btn-primary" data-testid="teacher-save">Simpan</button></div>
</form></div></div></div>
<script>
function editTeacher(t){ 
    document.getElementById('t-id').value=t.id; 
    document.getElementById('t-nip').value=t.nip||''; 
    document.getElementById('t-name').value=t.name||''; 
    document.getElementById('t-gender').value=t.gender||'L'; 
    document.getElementById('t-phone').value=t.phone||''; 
    document.getElementById('t-wa').value=t.whatsapp||''; 
    document.getElementById('t-email').value=t.email||''; 
    document.getElementById('t-addr').value=t.address||''; 
    
    // Auto-fill Fingerprint dan Leader saat edit
    document.getElementById('t-fingerprint').value=t.fingerprint_id||'';
    document.getElementById('t-leader').value=t.leader_id||'';
    
    new bootstrap.Modal(document.getElementById('teacher-modal')).show(); 
}
</script>