<h1 class="h4 mb-3">Suspicious logs</h1>
<div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
<thead><tr><th>ID</th><th>Type</th><th>User</th><th>IP</th><th>Context</th><th>Date</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= esc($r['id']) ?></td><td><?= esc($r['event']) ?></td><td><?= esc($r['user_id']) ?></td><td><?= esc($r['ip_address']) ?></td><td><code><?= esc($r['context_json']) ?></code></td><td><?= esc($r['created_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
