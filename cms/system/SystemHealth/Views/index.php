<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="h3 mb-1">Состояние системы</h1><div class="text-muted">Проверка окружения, готовности и production-настроек.</div></div>
    <div class="text-end"><div class="fs-3 fw-bold"><?= (int) ($score['percent'] ?? 0) ?>%</div><span class="badge <?= ! empty($score['ready']) ? 'bg-success' : 'bg-warning text-dark' ?>"><?= ! empty($score['ready']) ? 'Production ready' : 'Требуются исправления' ?></span></div>
</div>
<div class="row g-3 mb-3"><div class="col-md-6"><div class="card"><div class="card-body"><h2 class="h5">Liveness</h2><p><code><?= site_url('health') ?></code></p></div></div></div><div class="col-md-6"><div class="card"><div class="card-body"><h2 class="h5">Readiness</h2><p><code><?= site_url('ready') ?></code> — <?= ! empty($readiness['ready']) ? '<span class="text-success">READY</span>' : '<span class="text-danger">NOT READY</span>' ?></p></div></div></div></div>
<table class="table table-sm table-striped align-middle"><thead><tr><th>Проверка</th><th>Статус</th><th>Детали</th></tr></thead><tbody>
<?php foreach (($checks ?? []) as $c): ?><tr><td><?= esc($c['name']) ?></td><td><?= $c['ok'] ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">FAIL</span>' ?></td><td><small><?= esc($c['detail'] ?? '') ?></small></td></tr><?php endforeach; ?>
</tbody></table>
<div class="alert alert-warning">Backup выполняется средствами панели или сервера. CMS отвечает за preflight, блокировки, проверку цепочки обновлений и журнал операций.</div>
