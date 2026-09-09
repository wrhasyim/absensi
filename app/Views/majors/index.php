<?php use App\Core\Csrf; ?>
<div class="row g-3">
<div class="col-md-4"><div class="card"><div class="card-header">Tambah/Edit Jurusan</div><div class="card-body">
<form method="POST" action="<?= url('/majors/store') ?>">
  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
  <input type="hidden" name="id" id="major-id">
  <div class="mb-2"><label class="form-label">Kode</label><input required name="code" id="major-code" class="form-control" placeholder="TKJ" data-testid="major-code"></div>
  <div class="mb-2"><label class="form-label">Nama</label><input required name="name" id="major-name" class="form-control" placeholder="Teknik Komputer Jaringan" data-testid="major-name"></div>
  <div class="mb-2"><label class="form-label">Deskripsi</label><textarea name="description" id="major-desc" class="form-control"></textarea></div>
  <button class="btn btn-primary" data-testid="major-save"><i class="bi bi-save"></i> Simpan</button>
</form>
</div></div></div>
<div class="col-md-8"><div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Kode</th><th>Nama</th><th>Deskripsi</th><th></th></tr></thead>
<tbody data-testid="majors-list">
<?php foreach($majors as $m): ?>
<tr><td><b><?= e($m['code']) ?></b></td><td><?= e($m['name']) ?></td><td><?= e($m['description']) ?></td>
<td class="text-nowrap">
  <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('major-id').value='<?= $m['id'] ?>';document.getElementById('major-code').value='<?= e($m['code']) ?>';document.getElementById('major-name').value='<?= e($m['name']) ?>';document.getElementById('major-desc').value='<?= e($m['description']) ?>';"><i class="bi bi-pencil"></i></button>
  <form method="POST" action="<?= url('/majors/'.$m['id'].'/delete') ?>" data-confirm="Hapus?" class="d-inline"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><button class="btn btn-sm btn-outline-danger" data-testid="major-delete-<?= (int)$m['id'] ?>"><i class="bi bi-trash"></i></button></form>
</td></tr>
<?php endforeach; ?>
<?php if (empty($majors)): ?><tr><td colspan="4" class="text-center py-4 text-muted">Belum ada.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div>
