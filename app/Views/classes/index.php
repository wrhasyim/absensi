<?php use App\Core\Csrf; ?>
<div class="row g-3">
<div class="col-md-4"><div class="card"><div class="card-header">Tambah/Edit Kelas</div><div class="card-body">
<form method="POST" action="<?= url('/classes/store') ?>">
  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
  <input type="hidden" name="id" id="class-id">
  <div class="mb-2"><label class="form-label">Nama Kelas</label><input required name="name" id="class-name" class="form-control" placeholder="X TKJ 1" data-testid="class-name"></div>
  <div class="mb-2"><label class="form-label">Tingkat</label><select name="grade" id="class-grade" class="form-select"><option>X</option><option>XI</option><option>XII</option></select></div>
  <div class="mb-2"><label class="form-label">Jurusan</label><select name="major_id" id="class-major" class="form-select"><option value="">Pilih Jurusan</option>
    <?php foreach ($majors as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
  </select></div>
  <button class="btn btn-primary" data-testid="class-save"><i class="bi bi-save"></i> Simpan</button>
</form>
</div></div></div>
<div class="col-md-8"><div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Kelas</th><th>Tingkat</th><th>Jurusan</th><th></th></tr></thead>
<tbody data-testid="classes-list">
<?php foreach($classes as $c): ?>
<tr><td><b><?= e($c['name']) ?></b></td><td><?= e($c['grade']) ?></td><td><?= e($c['major_name'] ?? '-') ?></td>
<td class="text-nowrap">
  <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('class-id').value='<?= $c['id'] ?>';document.getElementById('class-name').value='<?= e($c['name']) ?>';document.getElementById('class-grade').value='<?= e($c['grade']) ?>';document.getElementById('class-major').value='<?= $c['major_id'] ?>';"><i class="bi bi-pencil"></i></button>
  <form method="POST" action="<?= url('/classes/'.$c['id'].'/delete') ?>" data-confirm="Hapus?" class="d-inline"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><button class="btn btn-sm btn-outline-danger" data-testid="class-delete-<?= (int)$c['id'] ?>"><i class="bi bi-trash"></i></button></form>
</td></tr>
<?php endforeach; ?>
<?php if (empty($classes)): ?><tr><td colspan="4" class="text-center py-4 text-muted">Belum ada.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div>
