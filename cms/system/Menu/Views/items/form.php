<h1 class="h4 mb-3"><?= esc($pageTitle ?? 'Пункт меню') ?></h1>
<form method="post" action="<?= site_url($item ? 'admin/menu/items/save/' . (int) $item['id'] : 'admin/menu/items/save') ?>" class="card card-body">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Меню</label>
            <select class="form-select" name="menu_key">
                <?php foreach ($menus as $menu): ?>
                    <option value="<?= esc($menu['menu_key']) ?>" <?= ($item['menu_key'] ?? $currentMenuKey) === $menu['menu_key'] ? 'selected' : '' ?>><?= esc($menu['title']) ?> (<?= esc($menu['menu_key']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Ключ пункта</label>
            <input class="form-control" name="item_key" value="<?= esc(old('item_key', $item['item_key'] ?? '')) ?>" <?= $item && (int) $item['is_system'] === 1 ? 'readonly' : '' ?> required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Родитель</label>
            <select class="form-select" name="parent_key">
                <option value="">— без родителя —</option>
                <?php foreach ($parents as $parent): if (!$item || $parent['item_key'] !== $item['item_key']): ?>
                    <option value="<?= esc($parent['item_key']) ?>" <?= ($item['parent_key'] ?? '') === $parent['item_key'] ? 'selected' : '' ?>><?= esc($parent['title']) ?></option>
                <?php endif; endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Название</label>
            <input class="form-control" name="title" value="<?= esc(old('title', $item['title'] ?? '')) ?>" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label">Тип ссылки</label>
            <?php $lt = old('link_type', $item['link_type'] ?? 'url'); ?>
            <select class="form-select" name="link_type">
                <?php foreach (['url'=>'URL','route'=>'Route','entity'=>'Entity','heading'=>'Заголовок','separator'=>'Разделитель'] as $k=>$v): ?>
                    <option value="<?= esc($k) ?>" <?= $lt === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-9 mb-3">
            <label class="form-label">URL</label>
            <input class="form-control" name="url" value="<?= esc(old('url', $item['url'] ?? '#')) ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3"><label class="form-label">Route name/path</label><input class="form-control" name="route_name" value="<?= esc(old('route_name', $item['route_name'] ?? '')) ?>"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Entity type</label><input class="form-control" name="entity_type" value="<?= esc(old('entity_type', $item['entity_type'] ?? '')) ?>"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Entity ID</label><input class="form-control" name="entity_id" value="<?= esc(old('entity_id', $item['entity_id'] ?? '')) ?>"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Langcode</label><input class="form-control" name="langcode" value="<?= esc(old('langcode', $item['langcode'] ?? '')) ?>" placeholder="uk, en"></div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3"><label class="form-label">Icon</label><input class="form-control" name="icon" value="<?= esc(old('icon', $item['icon'] ?? '')) ?>"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Модуль</label><input class="form-control" name="module" value="<?= esc(old('module', $item['module'] ?? '')) ?>"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Permission</label><input class="form-control" name="permission" value="<?= esc(old('permission', $item['permission'] ?? '')) ?>"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Target</label><select class="form-select" name="target"><?php $tg=old('target',$item['target']??''); ?><option value="">обычный</option><option value="_blank" <?= $tg==='_blank'?'selected':'' ?>>новое окно</option></select></div>
        <div class="col-md-3 mb-3"><label class="form-label">Вес</label><input class="form-control" type="number" name="weight" value="<?= esc(old('weight', $item['weight'] ?? 100)) ?>"></div>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= (int) old('is_active', $item['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label">Активно</label>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="<?= site_url('admin/menu?menu=' . urlencode($item['menu_key'] ?? $currentMenuKey)) ?>">Отмена</a>
    </div>
</form>
