<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Меню</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/menu/create') ?>">Создать меню</a>
        <a class="btn btn-sm btn-primary" href="<?= site_url('admin/menu/items/create?menu=' . urlencode($currentMenuKey)) ?>">Добавить пункт</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">Список меню</div>
            <div class="list-group list-group-flush">
                <?php foreach ($menus as $menu): ?>
                    <a class="list-group-item list-group-item-action <?= $menu['menu_key'] === $currentMenuKey ? 'active' : '' ?>" href="<?= site_url('admin/menu?menu=' . urlencode($menu['menu_key'])) ?>">
                        <div class="fw-semibold"><?= esc($menu['title']) ?></div>
                        <small><?= esc($menu['menu_key']) ?> · <?= esc($menu['area']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Пункты: <?= esc($currentMenuKey) ?></span>
                <?php foreach ($menus as $menu): if ($menu['menu_key'] === $currentMenuKey): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/menu/edit/' . (int) $menu['id']) ?>">Редактировать меню</a>
                <?php endif; endforeach; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Название</th>
                        <th>Ключ</th>
                        <th>Родитель</th>
                        <th>URL</th>
                        <th>Модуль</th>
                        <th>Вес</th>
                        <th>Статус</th>
                        <th class="text-end">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= $item['parent_key'] ? '&nbsp;&nbsp;&nbsp;↳ ' : '' ?><?= esc($item['title']) ?></td>
                            <td><code><?= esc($item['item_key']) ?></code></td>
                            <td><?= esc($item['parent_key'] ?: '—') ?></td>
                            <td><code><?= esc($item['url']) ?></code></td>
                            <td><?= esc($item['module'] ?: '—') ?></td>
                            <td><?= (int) $item['weight'] ?></td>
                            <td><?= (int) $item['is_active'] === 1 ? '<span class="badge bg-success">Вкл</span>' : '<span class="badge bg-secondary">Выкл</span>' ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/menu/items/edit/' . (int) $item['id']) ?>">Изм.</a>
                                <form class="d-inline" method="post" action="<?= site_url('admin/menu/items/toggle/' . (int) $item['id']) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-warning">Перекл.</button>
                                </form>
                                <?php if ((int) $item['is_system'] !== 1): ?>
                                    <form class="d-inline" method="post" action="<?= site_url('admin/menu/items/delete/' . (int) $item['id']) ?>" onsubmit="return confirm('Удалить пункт меню?')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger">Удалить</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$items): ?>
                        <tr><td colspan="8" class="text-muted">Пунктов меню нет.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
