<?php

namespace Modules\Kernel\Services;

final class SecretManager
{
    public function get(string $key, ?string $default = null): ?string
    {
        $value = env($key);
        if ($value === null || $value === '') {
            return $default;
        }
        return (string) $value;
    }

    public function require(string $key): string
    {
        $value = $this->get($key);
        if ($value === null || $value === '') {
            throw new \RuntimeException('Missing required secret: ' . $key);
        }
        return $value;
    }
}
