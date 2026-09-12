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
      <div class="col-md-auto"><button class="btn btn-primary"><i class="bi bi-search"></i> Filter</button></div>
      <div class="col text-end">
        <?php if (Auth::hasRole('super_admin','admin')): ?>
        <!-- Tombol Download diarahkan langsung ke file statis di folder public/assets -->
        <a href="<?= url('/assets/Format_Import_Siswa_Ortu.xlsx') ?>" class="btn btn-outline-secondary" title="Download Sample Excel" download><i class="bi bi-download"></i></a>
        
        <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#importModal" title="Import Massal"><i class="bi bi-file-earmark-excel"></i> Import</button>
        <a href="<?= url('/students/create') ?>" class="btn btn-success" data-testid="student-add"><i class="bi bi-plus-circle"></i> Tambah</a>
        <?php endif; ?>
      </div>
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

<!-- Modal Import Excel -->
<?php if (Auth::hasRole('super_admin','admin')): ?>
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-dark">
            <form action="<?= url('/students/import') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Data Siswa & Orang Tua</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file_excel" class="form-label">Pilih File Excel (.xlsx / .xls)</label>
                        <input class="form-control" type="file" id="file_excel" name="file_excel" accept=".xlsx, .xls" required>
                    </div>
                    <p class="text-muted small">Pastikan format kolom sesuai dengan Sample Excel. Kolom <b>ID Kelas</b> dan <b>ID Jurusan</b> diisi dengan angka. Data <b>Orang Tua</b> akan dibuat secara otomatis ke dalam database saat di-import.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-cloud-arrow-up"></i> Upload & Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>