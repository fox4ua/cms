<?php

namespace Modules\Menu\Services;

class MenuService
{
    public function tree(string $menuKey = 'admin_sidebar', ?string $langcode = null): array
    {
        try {
            $repo = new MenuRepositoryService();
            if (! $repo->getMenu($menuKey)) return $this->fallback($menuKey);
            $items = $repo->getItems($menuKey, true, $langcode);
            return $items ? (new MenuTreeService())->build($items) : $this->fallback($menuKey);
        } catch (\Throwable $e) {
            return $this->fallback($menuKey);
        }
    }

    public function flat(string $menuKey = 'admin_sidebar'): array
    {
        try { return (new MenuRepositoryService())->getItems($menuKey, false); } catch (\Throwable $e) { return []; }
    }

    public function buildTree(array $items): array
    {
        return (new MenuTreeService())->build($items);
    }

    public function href(array $item): string
    {
        return (new MenuRenderService())->href($item);
    }

    public function validator(): MenuValidationService
    {
        return new MenuValidationService();
    }

    public function validateMenuKey(string $key): bool { return $this->validator()->validateMenuKey($key); }
    public function validateItemKey(string $key): bool { return $this->validator()->validateItemKey($key); }
    public function validateUrl(string $url): bool { return $this->validator()->validateUrl($url); }

    private function fallback(string $menuKey): array
    {
        if ($menuKey !== 'admin_sidebar') return [];
        return [[
            'item_key' => 'system', 'link_type' => 'heading', 'title' => 'Система', 'url' => '#', 'icon' => 'boxes', 'children' => [
                ['item_key' => 'modules', 'link_type' => 'url', 'title' => 'Модули', 'url' => '/admin/modules', 'icon' => 'boxes', 'children' => []],
                ['item_key' => 'routes', 'link_type' => 'url', 'title' => 'Маршруты', 'url' => '/admin/routes', 'icon' => 'signpost', 'children' => []],
                ['item_key' => 'menu', 'link_type' => 'url', 'title' => 'Меню', 'url' => '/admin/menu', 'icon' => 'list', 'children' => []],
            ],
        ]];
    }
}
