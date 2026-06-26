<h1>IP правила</h1>
<?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
<?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div><?php endif; ?>
<form method="post" action="/admin/auth/ip-rules" class="row g-2 mb-4">
<?= csrf_field() ?>
<div class="col-md-3"><input class="form-control" name="ip_value" placeholder="IP или CIDR: 192.168.1.0/24"></div>
<div class="col-md-2"><select class="form-select" name="rule_type"><option value="allow">allow</option><option value="deny">deny</option></select></div>
<div class="col-md-5"><input class="form-control" name="description" placeholder="Описание"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Добавить</button></div>
</form>
<table class="table table-striped"><thead><tr><th>ID</th><th>IP/CIDR</th><th>Тип</th><th>Описание</th><th></th></tr></thead><tbody>
<?php foreach ($rules as $r): ?>
<tr><td><?= (int)$r['id'] ?></td><td><?= esc($r['ip_value']) ?></td><td><?= esc($r['rule_type']) ?></td><td><?= esc($r['description']) ?></td><td><form method="post" action="/admin/auth/ip-rules/delete/<?= (int)$r['id'] ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-danger" onclick="return confirm('Удалить IP-правило?')">Удалить</button></form></td></tr>
<?php endforeach; ?>
</tbody></table>
