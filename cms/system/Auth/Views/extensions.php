<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Расширения авторизации</h1><form method="post" action="/admin/auth/extensions/sync"><?= csrf_field() ?><button class="btn btn-primary">Синхронизировать hooks</button></form></div>
<div class="alert alert-info">Здесь будут отображаться hooks модулей AuthCaptcha, Auth2FA и других расширений. Auth вызывает их как pipeline без изменения основного AuthService.</div>
<table class="table table-sm table-striped align-middle">
<thead><tr><th>Hook</th><th>Модуль</th><th>Handler</th><th>Priority</th><th>Статус</th><th></th></tr></thead>
<tbody>
<?php foreach (($hooks ?? []) as $h): ?>
<tr>
<td><code><?= esc($h['hook_name']) ?></code></td><td><?= esc($h['module']) ?></td>
<td><small><?= esc($h['handler_class']) ?>::<?= esc($h['handler_method']) ?></small></td>
<td><?= (int)$h['priority'] ?></td>
<td><?= (int)$h['is_active'] ? '<span class="badge bg-success">active</span>' : '<span class="badge bg-secondary">disabled</span>' ?></td>
<td class="text-end"><form method="post" action="/admin/auth/extensions/toggle/<?= (int)$h['id'] ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Вкл/выкл</button></form></td>
</tr>
<?php endforeach; ?>
</tbody></table>
