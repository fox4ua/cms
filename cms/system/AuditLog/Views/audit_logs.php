<h1 class="h4 mb-3">Audit log</h1>
<div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
<thead><tr><th>ID</th><th>Admin</th><th>Action</th><th>Entity</th><th>IP</th><th>Date</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= esc($r['id']) ?></td><td><?= esc($r['admin_id']) ?></td><td><?= esc($r['action']) ?></td><td><?= esc(($r['entity_type'] ?? '') . ':' . ($r['entity_id'] ?? '')) ?></td><td><?= esc($r['ip_address']) ?></td><td><?= esc($r['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
