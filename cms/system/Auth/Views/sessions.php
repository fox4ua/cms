<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Активные сессии</h1>
    <form method="post" action="<?= site_url('/admin/auth/sessions/revoke-others') ?>"><?= csrf_field() ?><button class="btn btn-outline-danger btn-sm">Завершить все кроме текущей</button></form>
</div>
<?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
<div class="card"><div class="table-responsive"><table class="table mb-0 align-middle">
<thead><tr><th>ID</th><th>IP</th><th>Создана</th><th>Активность</th><th>Истекает</th><th>Статус</th><th></th></tr></thead><tbody>
<?php foreach ($sessions as $s): ?>
<tr>
<td><code><?= esc(substr($s['id'], 0, 10)) ?></code><?= $s['id'] === $currentSessionId ? ' <span class="badge text-bg-primary">текущая</span>' : '' ?></td>
<td><?= esc($s['ip_address']) ?></td>
<td><?= esc($s['created_at']) ?></td>
<td><?= esc($s['last_activity_at']) ?></td>
<td><?= esc($s['expires_at']) ?></td>
<td><?= $s['revoked_at'] ? '<span class="badge text-bg-secondary">завершена</span>' : '<span class="badge text-bg-success">активна</span>' ?></td>
<td class="text-end"><?php if (! $s['revoked_at'] && $s['id'] !== $currentSessionId): ?><form method="post" action="<?= site_url('/admin/auth/sessions/revoke/' . $s['id']) ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Завершить</button></form><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
