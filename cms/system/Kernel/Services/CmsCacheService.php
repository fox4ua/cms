<?php

namespace Modules\Kernel\Services;

class CmsCacheService
{
    private array $keys = [
        'cms_admin_menu',
        'cms_modules',
        'cms_settings_all',
        'cms_settings_groups',
        'kernel_auth_provider_class',
        'cms_routes_registry',
    ];

    public function clearKernel(): void
    {
        $cache = cache();
        foreach ($this->keys as $key) {
            $cache->delete($key);
        }
    }

    public function clearSettings(): void
    {
        cache()->delete('cms_settings_all');
        cache()->delete('cms_settings_groups');
    }

    public function clearMenu(): void
    {
        cache()->delete('cms_admin_menu');
    }

    public function clearModules(): void
    {
        cache()->delete('cms_modules');
    }

    public function clearRoutes(): void
    {
        cache()->delete('cms_routes_registry');
    }
}
