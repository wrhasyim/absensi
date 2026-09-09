<?php use App\Core\Csrf; $s=$student ?? null; ?>
<div class="card"><div class="card-body">
<form method="POST" action="<?= url($s ? '/students/'.$s['id'].'/update' : '/students/store') ?>" class="row g-3">
  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
  <div class="col-md-3"><label class="form-label">NIS *</label><input required name="nis" class="form-control" value="<?= e($s['nis'] ?? '') ?>" data-testid="student-nis"></div>
  <div class="col-md-3"><label class="form-label">NISN</label><input name="nisn" class="form-control" value="<?= e($s['nisn'] ?? '') ?>"></div>
  <div class="col-md-6"><label class="form-label">Nama Lengkap *</label><input required name="name" class="form-control" value="<?= e($s['name'] ?? '') ?>" data-testid="student-name"></div>
  <div class="col-md-3"><label class="form-label">Nama Panggilan</label><input name="nickname" class="form-control" value="<?= e($s['nickname'] ?? '') ?>"></div>
  <div class="col-md-2"><label class="form-label">Jenis Kelamin</label>
    <select name="gender" class="form-select"><option value="L" <?= ($s['gender']??'L')=='L'?'selected':'' ?>>Laki-laki</option><option value="P" <?= ($s['gender']??'')=='P'?'selected':'' ?>>Perempuan</option></select>
  </div>
  <div class="col-md-3"><label class="form-label">Tempat Lahir</label><input name="birth_place" class="form-control" value="<?= e($s['birth_place'] ?? '') ?>"></div>
  <div class="col-md-2"><label class="form-label">Tanggal Lahir</label><input type="date" name="birth_date" class="form-control" value="<?= e($s['birth_date'] ?? '') ?>"></div>
  <div class="col-md-2"><label class="form-label">Tahun Masuk</label><input type="number" name="entry_year" class="form-control" value="<?= e($s['entry_year'] ?? date('Y')) ?>"></div>
  <div class="col-md-3"><label class="form-label">Kelas</label>
    <select name="class_id" class="form-select" data-testid="student-class"><option value="">-Pilih-</option>
      <?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= ($s['class_id'] ?? '')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3"><label class="form-label">Jurusan</label>
    <select name="major_id" class="form-select"><option value="">-Pilih-</option>
      <?php foreach ($majors as $m): ?><option value="<?= $m['id'] ?>" <?= ($s['major_id'] ?? '')==$m['id']?'selected':'' ?>><?= e($m['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3"><label class="form-label">Fingerprint ID</label><input name="fingerprint_id" class="form-control" value="<?= e($s['fingerprint_id'] ?? '') ?>" data-testid="student-fp"></div>
  <div class="col-md-3"><label class="form-label">Status</label>
    <select name="status" class="form-select">
      <?php foreach (['aktif','pindah','lulus','keluar'] as $st): ?><option value="<?= $st ?>" <?= ($s['status'] ?? 'aktif')==$st?'selected':'' ?>><?= ucfirst($st) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3"><label class="form-label">No. HP Siswa</label><input name="phone" class="form-control" value="<?= e($s['phone'] ?? '') ?>"></div>
  <div class="col-md-3"><label class="form-label">WA Siswa</label><input name="whatsapp" class="form-control" placeholder="08xxx" value="<?= e($s['whatsapp'] ?? '') ?>"></div>
  <div class="col-md-3"><label class="form-label">Orang Tua</label>
    <select name="parent_id" class="form-select"><option value="">-Pilih-</option>
      <?php foreach ($parents as $p): ?><option value="<?= $p['id'] ?>" <?= ($s['parent_id'] ?? '')==$p['id']?'selected':'' ?>><?= e($p['father_name'] ?: $p['mother_name'] ?: $p['guardian_name']) ?> (<?= e($p['primary_whatsapp']) ?>)</option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-12"><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="2"><?= e($s['address'] ?? '') ?></textarea></div>
  <div class="col-12">
    <button class="btn btn-primary" data-testid="student-save"><i class="bi bi-save"></i> Simpan</button>
    <a href="<?= url('/students') ?>" class="btn btn-secondary">Batal</a>
  </div>
</form>
</div></div>
