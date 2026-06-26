<?php
$renderMenu = function (array $items, int $level = 0) use (&$renderMenu): void {
    foreach ($items as $i => $item) {
        $children = (array) ($item['children'] ?? []);
        $hasChildren = count($children) > 0;
        $title = (string) ($item['title'] ?? '');
        $icon = cms_menu_icon((string) ($item['icon'] ?? ''));
        $collapseId = 'menu_' . $level . '_' . (int) ($item['id'] ?? $i) . '_' . md5($title);
        $padding = 14 + ($level * 14);

        if ($hasChildren) {
            echo '<button class="cms-sidebar-link cms-sidebar-toggle" type="button" data-target="' . esc($collapseId, 'attr') . '" aria-expanded="true" style="padding-left:' . $padding . 'px">';
            echo '<span><span class="cms-icon">' . esc($icon) . '</span>' . esc($title) . '</span><span class="cms-chevron">⌄</span></button>';
            echo '<div class="cms-collapse show" id="' . esc($collapseId, 'attr') . '">';
            $renderMenu($children, $level + 1);
            echo '</div>';
        } else {
            echo '<a class="cms-sidebar-link" href="' . esc(cms_menu_href($item)) . '" style="padding-left:' . $padding . 'px">';
            echo '<span><span class="cms-icon">' . esc($icon) . '</span>' . esc($title) . '</span></a>';
        }
    }
};
?>
<aside class="cms-sidebar" id="cmsSidebar">
    <a class="cms-brand" href="<?= site_url('admin') ?>">
        <span class="cms-brand-icon">▣</span><strong>CMS Admin</strong>
    </a>
    <div class="cms-user">
        <div class="cms-avatar">●</div>
        <div>
            <div class="small text-white">Superadmin</div>
            <div class="text-secondary small"><?= esc($currentUser['email'] ?? '') ?></div>
        </div>
    </div>
    <div class="cms-menu-title">Навигация</div>
    <nav class="pb-3">
        <?php $renderMenu((array) ($adminMenu ?? [])); ?>
    </nav>
</aside>
