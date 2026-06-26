<?php

namespace Modules\Menu\Services;

class MenuValidationService
{
    public int $maxDepth = 4;

    public function validateMenuKey(string $key): bool
    {
        return (bool) preg_match('~^[a-z][a-z0-9_]{1,99}$~', $key);
    }

    public function validateItemKey(string $key): bool
    {
        return (bool) preg_match('~^[a-z][a-z0-9_.:-]{1,189}$~', $key);
    }

    public function validateLinkType(string $type): bool
    {
        return in_array($type, ['url','route','entity','separator','heading'], true);
    }

    public function validateUrl(string $url): bool
    {
        if ($url === '' || $url === '#') return true;
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return filter_var($url, FILTER_VALIDATE_URL) !== false;
        return str_starts_with($url, '/') && ! str_contains($url, ' ') && ! str_contains($url, '//');
    }

    public function validateTarget(string $target): bool
    {
        return $target === '' || in_array($target, ['_self','_blank'], true);
    }

    public function validateLangcode(string $langcode): bool
    {
        return $langcode === '' || (bool) preg_match('~^[a-z]{2}(-[A-Z]{2})?$~', $langcode);
    }

    public function wouldCreateCycle(array $items, string $itemKey, string $parentKey): bool
    {
        if ($parentKey === '' || $parentKey === $itemKey) return $parentKey === $itemKey;
        $parentMap = [];
        foreach ($items as $item) {
            $parentMap[(string) $item['item_key']] = (string) ($item['parent_key'] ?? '');
        }
        $parentMap[$itemKey] = $parentKey;
        $seen = [];
        $cursor = $itemKey;
        while ($cursor !== '') {
            if (isset($seen[$cursor])) return true;
            $seen[$cursor] = true;
            $cursor = $parentMap[$cursor] ?? '';
        }
        return false;
    }

    public function exceedsMaxDepth(array $items, string $itemKey, string $parentKey, int $maxDepth = 4): bool
    {
        $parentMap = [];
        foreach ($items as $item) {
            $parentMap[(string) $item['item_key']] = (string) ($item['parent_key'] ?? '');
        }
        $parentMap[$itemKey] = $parentKey;
        $depth = 1;
        $cursor = $itemKey;
        $guard = 0;
        while (($parentMap[$cursor] ?? '') !== '') {
            $depth++;
            $cursor = $parentMap[$cursor];
            if (++$guard > 50) return true;
        }
        return $depth > $maxDepth;
    }
}
