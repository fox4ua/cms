<h1 class="h4 mb-3"><?= esc($pageTitle ?? 'Меню') ?></h1>
<form method="post" action="<?= site_url($menu ? 'admin/menu/save/' . (int) $menu['id'] : 'admin/menu/save') ?>" class="card card-body">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Ключ меню</label>
        <input class="form-control" name="menu_key" value="<?= esc(old('menu_key', $menu['menu_key'] ?? '')) ?>" <?= $menu && (int) $menu['is_system'] === 1 ? 'readonly' : '' ?> required>
    </div>
    <div class="mb-3">
        <label class="form-label">Название</label>
        <input class="form-control" name="title" value="<?= esc(old('title', $menu['title'] ?? '')) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Описание</label>
        <input class="form-control" name="description" value="<?= esc(old('description', $menu['description'] ?? '')) ?>">
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Область</label>
            <input class="form-control" name="area" value="<?= esc(old('area', $menu['area'] ?? 'frontend')) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Модуль</label>
            <input class="form-control" name="module" value="<?= esc(old('module', $menu['module'] ?? '')) ?>">
        </div>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= (int) old('is_active', $menu['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label">Активно</label>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary">Сохранить</button>
        <a class="btn btn-outline-secondary" href="<?= site_url('admin/menu') ?>">Отмена</a>
    </div>
</form>
