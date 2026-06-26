<?php

namespace Modules\ModuleManager\Services;

class ModuleMenuService
{
    public function syncMenu(string $machineName, array $meta): void
    {
        $db = db_connect();
        if (! $db->tableExists('menus') || ! $db->tableExists('menu_items')) {
            return;
        }

        $menus = (array) ($meta['menus'] ?? []);
        if (!$menus && isset($meta['menu'])) {
            $menus['admin_sidebar'] = (array) $meta['menu'];
        }

        foreach ($menus as $menuKey => $items) {
            $this->ensureMenu((string) $menuKey, $this->titleFromMenuKey((string) $menuKey), (int) ($meta['is_system'] ?? 0));
            foreach ((array) $items as $item) {
                $this->upsertMenuItem((string) $menuKey, $machineName, $meta, (array) $item);
            }
        }
    }

    public function setModuleMenuActive(string $machineName, bool $active): void
    {
        $db = db_connect();
        if (! $db->tableExists('menu_items')) {
            return;
        }

        $db->table('menu_items')
            ->where('module', $machineName)
            ->update([
                'is_active' => $active ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    private function ensureMenu(string $menuKey, string $title, int $isSystem): void
    {
        $db = db_connect();
        $exists = $db->table('menus')->where('menu_key', $menuKey)->get()->getRowArray();
        if ($exists) {
            return;
        }
        $db->table('menus')->insert([
            'menu_key' => $menuKey,
            'title' => $title,
            'description' => '',
            'area' => str_starts_with($menuKey, 'admin_') ? 'admin' : 'frontend',
            'module' => 'Menu',
            'is_system' => $isSystem,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function upsertMenuItem(string $menuKey, string $machineName, array $meta, array $item): void
    {
        $db = db_connect();
        $url = (string) ($item['url'] ?? '#');
        $title = (string) ($item['title'] ?? '');
        if ($title === '') {
            return;
        }

        $parentKey = (string) ($item['parent_key'] ?? '');
        if ($parentKey !== '') {
            $this->ensureParent($menuKey, $parentKey, (string) ($item['parent_title'] ?? $this->titleFromKey($parentKey)), (int) ($item['parent_weight'] ?? 100));
        }

        $itemKey = (string) ($item['item_key'] ?? $item['menu_key'] ?? ($machineName . ':' . $url));
        $itemKey = $this->normalizeItemKey($itemKey);
        $data = [
            'menu_key' => $menuKey,
            'item_key' => $itemKey,
            'parent_key' => $parentKey,
            'title' => $title,
            'link_type' => (string) ($item['link_type'] ?? ($url === '#' ? 'heading' : 'url')),
            'url' => $url,
            'route_name' => (string) ($item['route_name'] ?? ''),
            'entity_type' => (string) ($item['entity_type'] ?? ''),
            'entity_id' => (string) ($item['entity_id'] ?? ''),
            'langcode' => (string) ($item['langcode'] ?? ''),
            'icon' => (string) ($item['icon'] ?? ''),
            'module' => $machineName,
            'permission' => (string) ($item['permission'] ?? ''),
            'target' => (string) ($item['target'] ?? ''),
            'weight' => (int) ($item['weight'] ?? 100),
            'is_active' => 1,
            'is_system' => (int) ($item['is_system'] ?? ($meta['is_system'] ?? 0)),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $exists = $db->table('menu_items')
            ->where('menu_key', $menuKey)
            ->where('item_key', $itemKey)
            ->get()->getRowArray();

        if ($exists) {
            $db->table('menu_items')->where('id', $exists['id'])->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->table('menu_items')->insert($data);
        }
    }

    private function ensureParent(string $menuKey, string $parentKey, string $title, int $weight): void
    {
        $db = db_connect();
        $exists = $db->table('menu_items')->where('menu_key', $menuKey)->where('item_key', $parentKey)->get()->getRowArray();
        if ($exists) {
            return;
        }

        $db->table('menu_items')->insert([
            'menu_key' => $menuKey,
            'item_key' => $parentKey,
            'parent_key' => '',
            'title' => $title,
            'link_type' => 'heading',
            'url' => '#',
            'route_name' => '',
            'entity_type' => '',
            'entity_id' => '',
            'langcode' => '',
            'icon' => $this->iconFromKey($parentKey),
            'module' => '',
            'permission' => '',
            'target' => '',
            'weight' => $weight,
            'is_active' => 1,
            'is_system' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function normalizeItemKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('~[^a-z0-9_.:-]+~', '_', $key) ?: 'item';
        return substr($key, 0, 190);
    }

    private function titleFromMenuKey(string $key): string
    {
        return match ($key) {
            'admin_sidebar' => 'Боковое меню админки',
            'admin_topbar' => 'Верхнее меню админки',
            'frontend_main' => 'Главное меню сайта',
            'frontend_footer' => 'Нижнее меню сайта',
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }

    private function titleFromKey(string $key): string
    {
        return match ($key) {
            'system' => 'Система',
            'security' => 'Безопасность',
            'configuration' => 'Конфигурация',
            'content' => 'Контент',
            'users' => 'Пользователи',
            default => ucfirst(str_replace(['_', '.'], ' ', $key)),
        };
    }

    private function iconFromKey(string $key): string
    {
        return match ($key) {
            'system' => 'boxes',
            'security' => 'shield-lock',
            'configuration' => 'gear',
            'content' => 'list',
            'users' => 'shield',
            default => 'circle',
        };
    }
}
