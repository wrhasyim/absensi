<div class="card mb-3"><div class="card-body">
<form method="GET" class="d-flex gap-2 align-items-end"><div><label class="form-label">Bulan</label><input type="month" name="month" value="<?= e($month) ?>" class="form-control"></div><div><button class="btn btn-primary">Tampilkan</button></div></form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th class="text-success">Hadir</th><th class="text-warning">Terlambat</th><th>Izin</th><th>Sakit</th><th class="text-danger">Alpa</th></tr></thead>
<tbody><?php foreach($rows as $r): ?>
<tr><td><?= e($r['nis']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['class_name']) ?></td>
<td><?= $r['hadir'] ?></td><td><?= $r['terlambat'] ?></td><td><?= $r['izin'] ?></td><td><?= $r['sakit'] ?></td><td><?= $r['alpa'] ?></td></tr>
<?php endforeach; ?></tbody></table></div></div>
