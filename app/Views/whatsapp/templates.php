<?php use App\Core\Csrf; ?>
<div class="alert alert-info small">
    <div class="mb-1"><b>Variabel Siswa:</b> <code>{nama_siswa}</code>, <code>{kelas}</code>, <code>{tanggal}</code>, <code>{jam}</code>, <code>{status}</code>, <code>{tipe}</code></div>
    <div><b>Variabel Guru:</b> <code>{nama_guru}</code>, <code>{tanggal}</code>, <code>{jam}</code>, <code>{tipe}</code></div>
</div>

<div class="row g-3">
<?php foreach($templates as $t): ?>
<div class="col-md-6"><div class="card"><div class="card-header d-flex justify-content-between"><span><code><?= e($t['code']) ?></code> — <?= e($t['name']) ?></span>
<span class="badge-status <?= $t['is_active']?'st-sent':'st-failed' ?>"><?= $t['is_active']?'AKTIF':'NONAKTIF' ?></span>
</div><div class="card-body">
<form method="POST" action="<?= url('/whatsapp/templates/save') ?>">
<input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><input type="hidden" name="id" value="<?= $t['id'] ?>">
<input type="hidden" name="code" value="<?= e($t['code']) ?>">
<div class="mb-2"><input name="name" class="form-control" value="<?= e($t['name']) ?>" placeholder="Nama Template"></div>
<div class="mb-2"><textarea name="content" class="form-control" rows="8"><?= e($t['content']) ?></textarea></div>
<div class="mb-2"><select name="is_active" class="form-select"><option value="1" <?= $t['is_active']?'selected':'' ?>>Aktif</option><option value="0" <?= !$t['is_active']?'selected':'' ?>>Nonaktif</option></select></div>
<button class="btn btn-primary" data-testid="tpl-save-<?= e($t['code']) ?>"><i class="bi bi-save"></i> Simpan</button>
</form>
</div></div></div>
<?php endforeach; ?>
</div>