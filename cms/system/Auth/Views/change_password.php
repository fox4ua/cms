<h1 class="h4 mb-3">Смена пароля</h1>
<?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div><?php endif; ?>
<form method="post" class="card card-body" action="<?= site_url('/admin/auth/password') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Текущий пароль</label>
        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
    </div>
    <div class="mb-3">
        <label class="form-label">Новый пароль</label>
        <input type="password" name="password" class="form-control" required autocomplete="new-password">
    </div>
    <div class="mb-3">
        <label class="form-label">Повтор нового пароля</label>
        <input type="password" name="password_confirm" class="form-control" required autocomplete="new-password">
    </div>
    <button class="btn btn-primary">Сменить пароль</button>
</form>
