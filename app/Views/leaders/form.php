<?php use App\Core\Csrf; $p=$l ?? null; ?>
<div class="card"><div class="card-body">
<form method="POST" action="<?= url($p ? '/leaders/'.$p['id'].'/update' : '/leaders/store') ?>" class="row g-3">
  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
  <div class="col-md-6"><label class="form-label">Nama Kepala Sekolah</label><input name="kepsek_name" class="form-control" value="<?= e($p['kepsek_name'] ?? '') ?>"></div>
  <div class="col-md-6"><label class="form-label">WA Kepala Sekolah</label><input name="kepsek_wa" class="form-control" value="<?= e($p['kepsek_wa'] ?? '') ?>" placeholder="08xxx / +60xxx"></div>
  <div class="col-md-6"><label class="form-label">Nama Kurikulum / Wakasek</label><input name="wakasek_name" class="form-control" value="<?= e($p['wakasek_name'] ?? '') ?>"></div>
  <div class="col-md-6"><label class="form-label">WA Kurikulum</label><input name="wakasek_wa" class="form-control" value="<?= e($p['wakasek_wa'] ?? '') ?>" placeholder="08xxx / +60xxx"></div>
  <div class="col-md-6"><label class="form-label">WA Utama (Target Notifikasi) *</label><input required name="primary_whatsapp" class="form-control" value="<?= e($p['primary_whatsapp'] ?? '') ?>" placeholder="08xxx / +60xxx"></div>
  <div class="col-md-6"><label class="form-label">Status</label>
    <select name="is_active" class="form-select"><option value="1">Aktif</option><option value="0" <?= isset($p['is_active']) && !$p['is_active']?'selected':'' ?>>Nonaktif</option></select>
  </div>
  <div class="col-12"><button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button> <a href="<?= url('/leaders') ?>" class="btn btn-secondary">Batal</a></div>
</form>
</div></div>