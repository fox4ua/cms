<?php
$module = $result['module'] ?? null;
$checks = $result['checks'] ?? [];
$canContinue = (bool) ($result['can_continue'] ?? false);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-0">Preflight-проверка модуля</h1>
        <div class="text-muted small">CMS проверяет корректность модуля. Backup выполняется средствами панели/сервера, не CMS.</div>
    </div>
    <a href="<?= site_url('/admin/modules') ?>" class="btn btn-outline-secondary btn-sm">Назад</a>
</div>

<?php if ($module): ?>
<div class="card mb-3"><div class="card-body">
    <strong><?= esc($module['name']) ?></strong><br>
    <span class="text-muted"><?= esc($module['machine_name']) ?></span><br>
    <span class="small">Операция: <strong><?= esc($operation) ?></strong></span>
</div></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="list-group list-group-flush">
        <?php foreach ($checks as $check): ?>
            <?php $level = $check['level']; $class = $level === 'ok' ? 'success' : ($level === 'warn' ? 'warning' : 'danger'); ?>
            <div class="list-group-item d-flex justify-content-between">
                <span><?= esc($check['message']) ?></span>
                <span class="badge text-bg-<?= $class ?>"><?= strtoupper(esc($level)) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($module && $canContinue): ?>
<form method="post" action="<?= site_url('/admin/modules/' . $operation . '/' . $module['id']) ?>" class="d-inline">
    <?= csrf_field() ?>
    <button class="btn btn-primary" onclick="return confirm('Продолжить операцию? Backup должен быть сделан средствами панели/сервера.')">Продолжить</button>
</form>
<?php else: ?>
<div class="alert alert-danger">Операцию нельзя продолжить, пока есть ERROR-проверки.</div>
<?php endif; ?>
