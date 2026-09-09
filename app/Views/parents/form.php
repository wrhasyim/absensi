<?php use App\Core\Csrf; $p=$parent ?? null; ?>
<div class="card"><div class="card-body">
<form method="POST" action="<?= url($p ? '/parents/'.$p['id'].'/update' : '/parents/store') ?>" class="row g-3">
  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
  <div class="col-md-6"><label class="form-label">Nama Ayah</label><input name="father_name" class="form-control" value="<?= e($p['father_name'] ?? '') ?>"></div>
  <div class="col-md-6"><label class="form-label">WA Ayah</label><input name="father_whatsapp" class="form-control" value="<?= e($p['father_whatsapp'] ?? '') ?>" placeholder="08xxx"></div>
  <div class="col-md-6"><label class="form-label">Nama Ibu</label><input name="mother_name" class="form-control" value="<?= e($p['mother_name'] ?? '') ?>"></div>
  <div class="col-md-6"><label class="form-label">WA Ibu</label><input name="mother_whatsapp" class="form-control" value="<?= e($p['mother_whatsapp'] ?? '') ?>" placeholder="08xxx"></div>
  <div class="col-md-6"><label class="form-label">Nama Wali</label><input name="guardian_name" class="form-control" value="<?= e($p['guardian_name'] ?? '') ?>"></div>
  <div class="col-md-6"><label class="form-label">WA Wali</label><input name="guardian_whatsapp" class="form-control" value="<?= e($p['guardian_whatsapp'] ?? '') ?>" placeholder="08xxx"></div>
  <div class="col-md-6"><label class="form-label">WA Utama *</label><input required name="primary_whatsapp" class="form-control" value="<?= e($p['primary_whatsapp'] ?? '') ?>" data-testid="parent-wa" placeholder="08xxx"></div>
  <div class="col-md-3"><label class="form-label">Hubungan</label><select name="relation" class="form-select">
    <?php foreach (['Orang Tua','Ayah','Ibu','Wali'] as $r): ?><option <?= ($p['relation']??'Orang Tua')==$r?'selected':'' ?>><?= $r ?></option><?php endforeach; ?>
  </select></div>
  <div class="col-md-3"><label class="form-label">Status</label>
    <select name="is_active" class="form-select"><option value="1">Aktif</option><option value="0" <?= isset($p['is_active']) && !$p['is_active']?'selected':'' ?>>Nonaktif</option></select>
  </div>
  <div class="col-12"><button class="btn btn-primary" data-testid="parent-save"><i class="bi bi-save"></i> Simpan</button> <a href="<?= url('/parents') ?>" class="btn btn-secondary">Batal</a></div>
</form>
</div></div>
