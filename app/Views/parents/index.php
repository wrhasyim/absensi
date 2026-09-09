<?php use App\Core\Csrf; ?>
<div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
  <span><i class="bi bi-person-hearts"></i> Data Orang Tua/Wali</span>
  <a href="<?= url('/parents/create') ?>" class="btn btn-sm btn-success" data-testid="parent-add"><i class="bi bi-plus-circle"></i> Tambah</a>
</div><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Ayah</th><th>Ibu</th><th>Wali</th><th>WA Utama</th><th>Hubungan</th><th>Status</th><th></th></tr></thead>
<tbody data-testid="parents-list">
<?php foreach($parents as $p): ?>
<tr>
  <td><?= e($p['father_name']) ?><br><small class="text-muted"><?= e($p['father_whatsapp']) ?></small></td>
  <td><?= e($p['mother_name']) ?><br><small class="text-muted"><?= e($p['mother_whatsapp']) ?></small></td>
  <td><?= e($p['guardian_name'] ?: '-') ?></td>
  <td><code><?= e($p['primary_whatsapp']) ?></code></td>
  <td><?= e($p['relation']) ?></td>
  <td><span class="badge-status <?= $p['is_active']?'st-hadir':'st-alpa' ?>"><?= $p['is_active']?'AKTIF':'NONAKTIF' ?></span></td>
  <td class="text-nowrap">
    <a href="<?= url('/parents/'.$p['id'].'/edit') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
    <form method="POST" action="<?= url('/parents/'.$p['id'].'/delete') ?>" data-confirm="Hapus?" class="d-inline"><input type="hidden" name="_csrf" value="<?= Csrf::token() ?>"><button class="btn btn-sm btn-outline-danger" data-testid="parent-delete-<?= (int)$p['id'] ?>"><i class="bi bi-trash"></i></button></form>
  </td>
</tr>
<?php endforeach; ?>
<?php if (empty($parents)): ?><tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data orang tua.</td></tr><?php endif; ?>
</tbody></table></div></div>
