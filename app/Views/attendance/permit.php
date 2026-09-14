<?php use App\Core\Csrf; ?>
<div class="card"><div class="card-body">
<form method="POST" action="<?= url('/attendance/permit') ?>" class="row g-3">
  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
  <div class="col-md-6"><label class="form-label">Pilih Pengguna *</label>
    <select required name="user_id" class="form-select" data-testid="permit-user"><option value="">-Pilih Siswa / Guru-</option>
      <?php foreach($users as $u): ?>
        <option value="<?= $u['role'][0] . '_' . $u['id'] ?>">
          [<?= $u['role'] === 'S_Siswa' ? 'Siswa' : 'Guru' ?>] <?= e($u['identifier']) ?> — <?= e($u['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3"><label class="form-label">Tanggal *</label><input required type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
  <div class="col-md-3"><label class="form-label">Status *</label>
    <select required name="status" class="form-select" data-testid="permit-status">
      <option value="izin">Izin</option><option value="sakit">Sakit</option><option value="dispensasi">Dispensasi</option>
    </select>
  </div>
  <div class="col-12"><label class="form-label">Keterangan</label><textarea name="note" class="form-control" rows="3" data-testid="permit-note"></textarea></div>
  <div class="col-12"><button class="btn btn-primary" data-testid="permit-save"><i class="bi bi-save"></i> Simpan</button></div>
</form>
</div></div>