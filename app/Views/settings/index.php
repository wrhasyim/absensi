<?php use App\Core\Csrf; $s=$settings; ?>
<div class="card"><div class="card-body">
<form method="POST" action="<?= url('/settings/save') ?>" class="row g-3">
  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
  <div class="col-md-3"><label class="form-label">Jam Masuk</label><input type="time" name="jam_masuk" class="form-control" value="<?= e($s['jam_masuk'] ?? '07:00') ?>" data-testid="set-jam-masuk"></div>
  <div class="col-md-3"><label class="form-label">Toleransi (menit)</label><input type="number" name="toleransi_menit" class="form-control" value="<?= e($s['toleransi_menit'] ?? '15') ?>"></div>
  <div class="col-md-3"><label class="form-label">Batas Belum Absen</label><input type="time" name="batas_belum_absen" class="form-control" value="<?= e($s['batas_belum_absen'] ?? '08:00') ?>"></div>
  <div class="col-md-3"><label class="form-label">Jam Pulang</label><input type="time" name="jam_pulang" class="form-control" value="<?= e($s['jam_pulang'] ?? '15:00') ?>"></div>
  <div class="col-md-4"><label class="form-label">Nama Sekolah</label><input name="nama_sekolah" class="form-control" value="<?= e($s['nama_sekolah'] ?? 'SMK Negeri 1') ?>"></div>
  <div class="col-md-4"><label class="form-label">Notif Terlambat</label><select name="notif_terlambat" class="form-select"><option value="1" <?= ($s['notif_terlambat']??'1')=='1'?'selected':'' ?>>Aktif</option><option value="0" <?= ($s['notif_terlambat']??'')=='0'?'selected':'' ?>>Nonaktif</option></select></div>
  <div class="col-md-4"><label class="form-label">Notif Pulang</label><select name="notif_pulang" class="form-select"><option value="0" <?= ($s['notif_pulang']??'0')=='0'?'selected':'' ?>>Nonaktif</option><option value="1" <?= ($s['notif_pulang']??'')=='1'?'selected':'' ?>>Aktif</option></select></div>
  <div class="col-12"><button class="btn btn-primary" data-testid="settings-save"><i class="bi bi-save"></i> Simpan Pengaturan</button></div>
</form>
</div></div>
