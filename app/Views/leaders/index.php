<?php use App\Core\Csrf; ?>
<div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
  <span><i class="bi bi-person-badge"></i> Data Pimpinan (Kepsek & Kurikulum)</span>
  <a href="<?= url('/leaders/create') ?>" class="btn btn-sm btn-success"><i class="bi bi-plus-circle"></i> Tambah</a>
</div><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Kepala Sekolah</th><th>Wakasek Kurikulum</th><th>WA Utama</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach($leaders as $l): ?>
<tr>
  <td><?= e($l['kepsek_name']) ?><br><small class="text-muted"><?= e($l['kepsek_wa']) ?></small></td>
  <td><?= e($l['wakasek_name']) ?><br><small class="text-muted"><?= e($l['wakasek_wa']) ?></small></td>
  <td><code><?= e($l['primary_whatsapp']) ?></code></td>
  <td><span class="badge-status <?= $l['is_active']?'st-hadir':'st-alpa' ?>"><?= $l['is_active']?'AKTIF':'NONAKTIF' ?></span></td>
  <td class="text-nowrap">
    <a href="<?= url('/leaders/'.$l['id'].'/edit') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
    <form method="POST" action="<?= url('/leaders/'.$l['id'].'/delete') ?>" class="d-inline" data-confirm="Hapus?">
      <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
<?php if (empty($leaders)): ?><tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data pimpinan.</td></tr><?php endif; ?>
</tbody></table></div></div>