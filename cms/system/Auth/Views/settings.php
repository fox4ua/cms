<h1>Настройки авторизации</h1>
<?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
<form method="post" action="/admin/auth/settings">
<?= csrf_field() ?>
<div class="row g-3">
<?php
$fields = [
 'max_failed_attempts'=>'Максимум ошибок входа', 'block_base_seconds'=>'Базовая блокировка, сек', 'block_multiplier'=>'Множитель блокировки',
 'session_idle_ttl'=>'Idle TTL сессии, сек', 'session_absolute_ttl'=>'Абсолютный TTL сессии, сек', 'remember_enabled'=>'Remember me включён',
 'remember_me_expiry'=>'Срок remember me, сек', 'remember_rotate_after'=>'Ротация remember me, сек', 'password_min_length'=>'Минимальная длина пароля',
 'password_require_upper'=>'Нужны большие буквы', 'password_require_lower'=>'Нужны маленькие буквы', 'password_require_digit'=>'Нужны цифры',
 'password_require_special'=>'Нужны спецсимволы', 'password_history_count'=>'Запрет последних паролей', 'password_expires_days'=>'Срок действия пароля, дней',
 'captcha_enabled'=>'CAPTCHA включена', 'captcha_after_attempts'=>'CAPTCHA после N ошибок', 'captcha_skip_internal_ip'=>'Не показывать CAPTCHA для внутренних IP',
 'two_factor_skip_internal_ip'=>'Не требовать 2FA для внутренних IP', 'admin_ip_allowlist_enabled'=>'Белый список IP для админки включён'
];
foreach ($fields as $key=>$label): ?>
  <div class="col-md-4">
    <label class="form-label"><?= esc($label) ?></label>
    <input class="form-control" name="<?= esc($key) ?>" value="<?= esc($settings[$key] ?? '') ?>">
  </div>
<?php endforeach; ?>
  <div class="col-md-4">
    <label class="form-label">Режим 2FA</label>
    <select class="form-select" name="two_factor_mode">
      <?php foreach (['off'=>'Выключено','optional'=>'Опционально','required'=>'Обязательно'] as $v=>$t): ?>
        <option value="<?= $v ?>" <?= ($settings['two_factor_mode'] ?? '') === $v ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12">
    <label class="form-label">Внутренние IP/CIDR, по одному в строке</label>
    <textarea class="form-control" name="internal_ip_ranges" rows="5"><?= esc($settings['internal_ip_ranges'] ?? '') ?></textarea>
  </div>
</div>
<button class="btn btn-primary mt-3">Сохранить</button>
</form>
