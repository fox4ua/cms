<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle ?? 'CMS') ?></title>
    <link href="<?= base_url('assets/admin/admin.css') ?>" rel="stylesheet">
</head>
<body>
<div class="cms-wrapper">
    <?= view('Modules\Kernel\Views\partials\sidebar', get_defined_vars()) ?>

    <div class="cms-main">
        <?= view('Modules\Kernel\Views\partials\topbar', get_defined_vars()) ?>
        <main class="cms-content">
            <?= view('Modules\Kernel\Views\partials\flash') ?>
            <?= $content ?? '' ?>
        </main>
    </div>
</div>
<script src="<?= base_url('assets/admin/admin.js') ?>"></script>
</body>
</html>
