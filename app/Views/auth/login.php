<?php use App\Core\Csrf; ?>
<?php if ($m = flash('error')): ?><div class="alert alert-danger" data-testid="login-error"><?= e($m) ?></div><?php endif; ?>
<form method="POST" action="<?= url('/login') ?>" class="auth-form">
  <input type="hidden" name="_csrf" value="<?= Csrf::token() ?>">
  <div class="form-floating mb-3">
    <input type="text" name="username" class="form-control" id="username" placeholder="Username" required autofocus data-testid="login-username">
    <label for="username">Username</label>
  </div>
  <div class="form-floating mb-3">
    <input type="password" name="password" class="form-control" id="password" placeholder="Password" required data-testid="login-password">
    <label for="password">Password</label>
  </div>
  <button type="submit" class="btn btn-primary w-100 btn-lg" data-testid="login-submit">Masuk</button>
</form>
<div class="auth-hint">
  <small>Default: <b>admin</b> / <b>admin123</b></small>
</div>
