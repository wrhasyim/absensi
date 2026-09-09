<?php use App\Core\Csrf; use App\Core\Auth; ?>
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4"><label class="form-label">Cari</label><input type="text" name="q" value="<?= e($q) ?>" placeholder="Nama / NIS / NISN" class="form-control" data-testid="student-search"></div>
      <div class="col-md-3"><label class="form-label">Kelas</label>
        <select name="class_id" class="form-select" data-testid="student-class-filter">
          <option value="">Semua Kelas</option>
          <?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><button class="btn btn-primary"><i class="bi bi-search"></i> Filter</button></div>
      <div class="col-md-2 text-end"><?php if (Auth::hasRole('super_admin','admin')): ?><a href="<?= url('/students/create') ?>" class="btn btn-success" data-testid="student-add"><i class="bi bi-plus-circle"></i> Tambah</a><?php endif; ?></div>
    </form>
  </div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
  <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Jurusan</th><th>FP ID</th><th>WA</th><th>Status</th><th></th></tr></thead>
  <tbody data-testid="students-table">
    <?php foreach ($students as $s): ?>
    <tr>
      <td><?= e($s['nis']) ?></td>
      <td><b><?= e($s['name']) ?></b><br><small class="text-muted"><?= e($s['nickname']) ?></small></td>
      <td><?= e($s['class_name'] ?? '-') ?></td>
      <td><?= e($s['major_code'] ?? '-') ?></td>
      <td><code><?= e($s['fingerprint_id'] ?? '-') ?></code></td>
      <td><?= e($s['whatsapp'] ?? '-') ?></td>
      <td><span class="badge-status st-hadir"><?= strtoupper($s['status']) ?></span></td>
      <td class="text-nowrap">
        <?php if (Auth::hasRole('super_admin','admin')): ?>
        <a href="<?= url('/students/'.$s['id'].'/edit') ?>" class="btn btn-sm btn-outline-primary" data-testid="student-edit-<?= (int)$s['id'] ?>"><i class="bi bi-pencil"></i></a>
        <form method="POST" action="<?= url('/students/'.$s['id'].'/delete') ?>" data-confirm="Hapus siswa ini?" class="d-inline">
          <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
          <button class="btn btn-sm btn-outline-danger" data-testid="student-delete-<?= (int)$s['id'] ?>"><i class="bi bi-trash"></i></button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($students)): ?><tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data siswa.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
