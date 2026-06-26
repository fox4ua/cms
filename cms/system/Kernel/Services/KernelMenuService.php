<?php

namespace Modules\Kernel\Services;

class KernelMenuService
{
    public function getMenu(): array
    {
        try {
            if (class_exists('\\Modules\\Menu\\Services\\MenuService')) {
                return (new \Modules\Menu\Services\MenuService())->tree('admin_sidebar');
            }
        } catch (\Throwable $e) {
        }

        return [[
            'title' => 'Система',
            'url' => '#',
            'icon' => 'boxes',
            'children' => [
                ['title' => 'Модули', 'url' => '/admin/modules', 'icon' => 'boxes', 'children' => []],
                ['title' => 'Маршруты', 'url' => '/admin/routes', 'icon' => 'signpost', 'children' => []],
                ['title' => 'Меню', 'url' => '/admin/menu', 'icon' => 'list', 'children' => []],
            ],
        ]];
    }
}
