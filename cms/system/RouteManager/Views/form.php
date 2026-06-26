<?php $r = $route ?? []; $isEdit = ! empty($r['id']); ?>
<h1 class="h3 mb-3"><?= $isEdit ? 'Редактировать маршрут' : 'Создать маршрут' ?></h1>
<?php if (! empty($errorsList)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errorsList as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="<?= $isEdit ? '/admin/routes/save/' . (int)$r['id'] : '/admin/routes/save' ?>" class="card card-body">
<?= csrf_field() ?>
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Module</label><input name="module" class="form-control" value="<?= esc($r['module'] ?? '') ?>" required></div>
<div class="col-md-3"><label class="form-label">HTTP method</label><select name="http_method" class="form-select"><?php foreach (['GET','POST','PUT','PATCH','DELETE','MATCH','ANY'] as $m): ?><option value="<?= $m ?>" <?= (($r['http_method'] ?? 'GET') === $m) ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Path</label><input name="path" class="form-control" value="<?= esc($r['path'] ?? '') ?>" placeholder="/admin/example" required></div>
<div class="col-md-6"><label class="form-label">Route key</label><input name="route_key" class="form-control" value="<?= esc($r['route_key'] ?? '') ?>" placeholder="Module:GET:/path"></div>
<div class="col-md-6"><label class="form-label">Controller</label><input name="controller" class="form-control" value="<?= esc($r['controller'] ?? '') ?>" placeholder="\Modules\Example\Controllers\ExampleController" required></div>
<div class="col-md-4"><label class="form-label">Action</label><input name="action" class="form-control" value="<?= esc($r['action'] ?? 'index') ?>" required></div>
<div class="col-md-2"><label class="form-label">Sort</label><input name="sort_order" type="number" class="form-control" value="<?= esc($r['sort_order'] ?? 100) ?>"></div>
<div class="col-md-6 d-flex align-items-end gap-4">
<label><input type="checkbox" name="is_admin" value="1" <?= ! empty($r['is_admin']) ? 'checked' : '' ?>> Admin</label>
<label><input type="checkbox" name="is_active" value="1" <?= ! isset($r['is_active']) || ! empty($r['is_active']) ? 'checked' : '' ?>> Active</label>
<label><input type="checkbox" name="is_system" value="1" <?= ! empty($r['is_system']) ? 'checked' : '' ?>> System</label>
</div>
</div>
<div class="mt-3"><button class="btn btn-primary">Сохранить</button> <a href="/admin/routes" class="btn btn-light">Назад</a></div>
</form>
