<?php

namespace Modules\Menu\Services;

class MenuRenderService
{
    public function href(array $item): string
    {
        $type = (string) ($item['link_type'] ?? 'url');
        if (in_array($type, ['heading','separator'], true)) return '#';
        if ($type === 'route' && !empty($item['route_name'])) return site_url(ltrim((string) $item['route_name'], '/'));
        if ($type === 'entity' && !empty($item['entity_type']) && !empty($item['entity_id'])) {
            return site_url($item['entity_type'] . '/' . $item['entity_id']);
        }
        $url = (string) ($item['url'] ?? '#');
        if ($url === '#' || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return $url;
        return site_url(ltrim($url, '/'));
    }
}
