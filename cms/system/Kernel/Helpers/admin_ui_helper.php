<?php

if (! function_exists('cms_menu_icon')) {
    function cms_menu_icon(string $icon): string
    {
        $map = [
            'speedometer' => '⚡', 'boxes' => '▦', 'list' => '☰', 'shield-lock' => '▣',
            'globe' => '◎', 'shield' => '◇', 'key' => '⚿', 'plug' => '⌁',
            'signpost' => '↦', 'activity' => '◌', 'gear' => '⚙', 'warning' => '⚠',
        ];
        return $map[$icon] ?? '•';
    }
}

if (! function_exists('cms_menu_href')) {
    function cms_menu_href(array $item): string
    {
        $type = (string) ($item['link_type'] ?? 'url');
        if (in_array($type, ['heading', 'separator'], true)) return '#';
        if ($type === 'route' && ! empty($item['route_name'])) return site_url(ltrim((string) $item['route_name'], '/'));
        if ($type === 'entity' && ! empty($item['entity_type']) && ! empty($item['entity_id'])) return site_url($item['entity_type'] . '/' . $item['entity_id']);
        $url = (string) ($item['url'] ?? '#');
        if ($url === '#' || $url === '') return '#';
        return preg_match('~^https?://~i', $url) ? $url : site_url(ltrim($url, '/'));
    }
}
