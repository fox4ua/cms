<header class="cms-topbar">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm cms-mobile-toggle" type="button" data-toggle-sidebar>☰</button>
        <span class="fw-semibold"><?= esc($pageTitle ?? 'Панель управления') ?></span>
    </div>
    <form method="post" action="<?= site_url('logout') ?>" class="mb-0">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-danger" type="submit">Выход</button>
    </form>
</header>
