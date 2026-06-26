<?php

namespace Modules\Kernel\Services;

use Modules\Kernel\Contracts\AuthenticationProviderInterface;

final class AuthenticationProviderResolver
{
    private const CACHE_KEY = 'kernel_auth_provider_class';

    public function provider(): ?AuthenticationProviderInterface
    {
        $cachedClass = cache(self::CACHE_KEY);
        if (is_string($cachedClass) && $cachedClass !== '' && class_exists($cachedClass)) {
            $provider = new $cachedClass();
            if ($provider instanceof AuthenticationProviderInterface) {
                return $provider;
            }
            cache()->delete(self::CACHE_KEY);
        }

        foreach ((new ModulePathService())->manifests() as $manifest) {
            $meta = include $manifest['file'];
            if (! is_array($meta) || empty($meta['auth_provider'])) {
                continue;
            }
            $class = (string) $meta['auth_provider'];
            if (! class_exists($class)) {
                continue;
            }
            $provider = new $class();
            if ($provider instanceof AuthenticationProviderInterface) {
                cache()->save(self::CACHE_KEY, $class, 3600);
                return $provider;
            }
        }
        return null;
    }
}
