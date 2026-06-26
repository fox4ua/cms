<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Маршруты CMS</h1>
    <div class="d-flex gap-2">
        <a href="/admin/routes/create" class="btn btn-success">Добавить маршрут</a>
        <form method="post" action="/admin/routes/sync"><?= csrf_field() ?><button class="btn btn-primary">Синхронизировать из module.php</button></form>
    </div>
</div>
<div class="alert alert-info">Обычные маршруты регистрируются из <code>cms_routes</code>. Bootstrap routes в файле нужны только для аварийного входа и запуска RouteManager.</div>
<table class="table table-sm table-striped align-middle">
<thead><tr><th>Модуль</th><th>Метод</th><th>Путь</th><th>Контроллер</th><th>Action</th><th>Статус</th><th></th></tr></thead>
<tbody>
<?php foreach (($routesList ?? []) as $r): ?>
<tr>
<td><?= esc($r['module']) ?></td>
<td><span class="badge bg-secondary"><?= esc($r['http_method']) ?></span></td>
<td><code><?= esc($r['path']) ?></code></td>
<td><small><?= esc($r['controller']) ?></small></td>
<td><?= esc($r['action']) ?></td>
<td><?= (int)$r['is_active'] ? '<span class="badge bg-success">active</span>' : '<span class="badge bg-secondary">disabled</span>' ?> <?= (int)$r['is_system'] ? '<span class="badge bg-warning text-dark">system</span>' : '' ?></td>
<td class="text-end">
    <a class="btn btn-sm btn-outline-primary" href="/admin/routes/edit/<?= (int)$r['id'] ?>">Редактировать</a>
    <?php if ((int)$r['is_system'] !== 1): ?>
    <form method="post" action="/admin/routes/toggle/<?= (int)$r['id'] ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Вкл/выкл</button></form>
    <form method="post" action="/admin/routes/delete/<?= (int)$r['id'] ?>" class="d-inline" onsubmit="return confirm('Удалить маршрут?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Удалить</button></form>
    <?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody></table>
