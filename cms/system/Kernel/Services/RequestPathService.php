<?php

namespace Modules\Kernel\Services;

final class RequestPathService
{
    public function path(): string
    {
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = '/' . ltrim($path, '/');

        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script !== '' && $script !== '/' && str_starts_with($path, $script)) {
            $path = substr($path, strlen($script)) ?: '/';
        } else {
            $scriptDir = rtrim(str_replace('\\', '/', dirname($script)), '/.');
            if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir . '/')) {
                $path = substr($path, strlen($scriptDir)) ?: '/';
            }
            if ($path === '/index.php') {
                $path = '/';
            } elseif (str_starts_with($path, '/index.php/')) {
                $path = substr($path, strlen('/index.php')) ?: '/';
            }
        }

        return '/' . ltrim($path, '/');
    }
}
