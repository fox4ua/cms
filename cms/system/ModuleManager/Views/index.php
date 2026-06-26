<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-0">Менеджер модулей</h1>
        <div class="text-muted small">Установка, включение и обновление модулей из папки <code>/cms/system и /cms/modules</code></div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('/admin/modules/operations') ?>">Журнал операций</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('/admin/modules/locks') ?>">Блокировки</a>
        <form method="post" action="<?= site_url('/admin/modules/sync') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-primary btn-sm">Синхронизировать</button>
        </form>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Модуль</th><th>Установлена</th><th>Доступна</th><th>Статус</th><th>Источник</th><th>Системный</th><th class="text-end">Действие</th></tr></thead>
            <tbody>
            <?php foreach ($modules as $module): ?>
                <?php
                $installed = (int) ($module['is_installed'] ?? 1) === 1;
                $enabled = (int) $module['is_enabled'] === 1;
                $installedVersion = $module['installed_version'] ?: ($installed ? $module['version'] : '0.0.0');
                $availableVersion = $module['available_version'] ?: $module['version'];
                $hasUpdate = $installed && version_compare($availableVersion, $installedVersion, '>');
                ?>
                <tr>
                    <td>
                        <strong><?= esc($module['name']) ?></strong><br>
                        <small class="text-muted"><?= esc($module['machine_name']) ?> — <?= esc($module['description']) ?></small>
                        <?php if (! empty($module['dependencies']) && $module['dependencies'] !== '[]'): ?><br><small class="text-muted">Зависимости: <?= esc($module['dependencies']) ?></small><?php endif; ?>
                        <?php if (! empty($module['last_error'])): ?><br><small class="text-danger">Ошибка: <?= esc($module['last_error']) ?></small><?php endif; ?>
                    </td>
                    <td><?= esc($installedVersion) ?></td>
                    <td><?= esc($availableVersion) ?><?php if ($hasUpdate): ?><span class="badge text-bg-warning ms-1">Есть обновление</span><?php endif; ?></td>
                    <td>
                        <?php if (! $installed): ?><span class="badge text-bg-dark">Не установлен</span><?php elseif ($enabled): ?><span class="badge text-bg-success">Включён</span><?php else: ?><span class="badge text-bg-secondary">Выключен</span><?php endif; ?>
                        <div class="small text-muted"><?= esc($module['install_status'] ?? 'installed') ?></div>
                    </td>
                    <td><span class="badge text-bg-light border"><?= esc($module['source_type'] ?? 'modules') ?></span></td>
                    <td><?= (int) $module['is_system'] === 1 ? 'Да' : 'Нет' ?></td>
                    <td class="text-end">
                        <?php if (! $installed): ?>
                            <a class="btn btn-sm btn-success" href="<?= site_url('/admin/modules/preflight/' . $module['id'] . '/install') ?>">Проверить и установить</a>
                        <?php else: ?>
                            <?php if ($hasUpdate): ?><a class="btn btn-sm btn-warning" href="<?= site_url('/admin/modules/preflight/' . $module['id'] . '/update') ?>">Проверить и обновить</a><?php endif; ?>
                            <?php if ($enabled): ?>
                                <?php if ((int) $module['is_system'] !== 1): ?><form method="post" action="<?= site_url('/admin/modules/disable/' . $module['id']) ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Выключить модуль? Его URL станут недоступны.')">Выключить</button></form><?php else: ?><span class="text-muted">Защищён</span><?php endif; ?>
                            <?php else: ?>
                                <form method="post" action="<?= site_url('/admin/modules/enable/' . $module['id']) ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-success">Включить</button></form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info mt-3 small mb-0">
    Опасные действия выполняются только через POST + CSRF. Установка и обновление проходят preflight-проверку. Backup выполняется средствами панели/сервера.
</div>
