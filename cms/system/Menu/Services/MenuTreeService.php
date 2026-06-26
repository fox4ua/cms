<?php

namespace Modules\Menu\Services;

class MenuTreeService
{
    public function build(array $items): array
    {
        $byKey = [];
        foreach ($items as $item) {
            $item['children'] = [];
            $byKey[(string) $item['item_key']] = $item;
        }
        $tree = [];
        foreach ($byKey as $key => &$item) {
            $parentKey = (string) ($item['parent_key'] ?? '');
            if ($parentKey !== '' && isset($byKey[$parentKey]) && $parentKey !== $key) {
                $byKey[$parentKey]['children'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }
        unset($item);
        return $tree;
    }
}
