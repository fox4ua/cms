<?php

namespace Modules\Menu\Services;

class MenuRepositoryService
{
    public function getMenu(string $menuKey): ?array
    {
        $db = db_connect();
        if (! $db->tableExists('menus')) return null;
        return $db->table('menus')->where('menu_key', $menuKey)->where('is_active', 1)->get()->getRowArray() ?: null;
    }

    public function getItems(string $menuKey, bool $activeOnly = true, ?string $langcode = null): array
    {
        $db = db_connect();
        if (! $db->tableExists('menu_items')) return [];
        $builder = $db->table('menu_items mi')
            ->select('mi.*')
            ->join('cms_modules cm', 'cm.machine_name = mi.module', 'left')
            ->where('mi.menu_key', $menuKey)
            ->where('mi.deleted_at', null);
        if ($activeOnly) {
            $builder->where('mi.is_active', 1)
                ->groupStart()
                    ->where('mi.module', null)
                    ->orWhere('mi.module', '')
                    ->orWhereIn('mi.link_type', ['heading','separator'])
                    ->orGroupStart()
                        ->where('cm.is_installed', 1)
                        ->where('cm.is_enabled', 1)
                    ->groupEnd()
                ->groupEnd();
        }
        if ($langcode) {
            $builder->groupStart()->where('mi.langcode', null)->orWhere('mi.langcode', '')->orWhere('mi.langcode', $langcode)->groupEnd();
        }
        return $builder->orderBy('mi.weight', 'ASC')->orderBy('mi.id', 'ASC')->get()->getResultArray();
    }
}
