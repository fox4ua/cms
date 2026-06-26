<?php

namespace Modules\Kernel\Services;

use RuntimeException;
use Throwable;

final class RateLimitService
{
    public function enforce(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $path = (new RequestPathService())->path();
        [$limit, $window] = $this->rule($path);
        $ip = (new TrustedProxyService())->clientIp();
        $directory = WRITEPATH . 'cache/rate-limit';
        try {
            if (! is_dir($directory) && ! @mkdir($directory, 0770, true) && ! is_dir($directory)) {
                throw new RuntimeException('Не удалось создать каталог rate limit.');
            }

            $retryAfter = max(
                $this->consume($directory, hash('sha256', $ip . '|global|' . $method), (int) env('CMS_RATE_GLOBAL_MAX', 300), (int) env('CMS_RATE_GLOBAL_WINDOW', 60)),
                $this->consume($directory, hash('sha256', $ip . '|' . $method . '|' . $this->normalizePath($path)), $limit, $window)
            );
        } catch (Throwable $e) {
            log_message('critical', 'Rate limit storage failure: ' . $e->getMessage());
            if (! filter_var(env('CMS_RATE_FAIL_CLOSED', true), FILTER_VALIDATE_BOOL)) {
                return;
            }
            if (PHP_SAPI !== 'cli' && ! headers_sent()) {
                header('Content-Type: text/plain; charset=utf-8', true);
                header('Retry-After: 60', true);
            }
            http_response_code(503);
            echo 'Сервис временно недоступен.';
            exit;
        }

        if ($retryAfter > 0) {
            service('response')->setHeader('Retry-After', (string) $retryAfter);
            if (PHP_SAPI !== 'cli' && ! headers_sent()) {
                header('Retry-After: ' . $retryAfter, true);
                header('Content-Type: text/plain; charset=utf-8', true);
            }
            http_response_code(429);
            echo 'Слишком много запросов. Повторите позже.';
            exit;
        }

        $this->garbageCollect($directory);
    }

    private function consume(string $directory, string $bucket, int $limit, int $window): int
    {
        if ($limit <= 0 || $window <= 0) {
            return 0;
        }
        $file = $directory . '/' . $bucket . '.json';
        $handle = @fopen($file, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Не удалось открыть хранилище rate limit.');
        }

        $now = time();
        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException('Не удалось заблокировать хранилище rate limit.');
            }
            rewind($handle);
            $state = json_decode(stream_get_contents($handle) ?: '{}', true);
            if (! is_array($state) || (int) ($state['started_at'] ?? 0) + $window <= $now) {
                $state = ['started_at' => $now, 'count' => 0];
            }
            $state['count'] = (int) ($state['count'] ?? 0) + 1;
            $retryAfter = $state['count'] > $limit ? max(1, ((int) $state['started_at'] + $window) - $now) : 0;
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($state, JSON_UNESCAPED_SLASHES));
            fflush($handle);
            flock($handle, LOCK_UN);
            return $retryAfter;
        } finally {
            fclose($handle);
        }
    }


    private function rule(string $path): array
    {
        if ($path === '/login') {
            return [(int) env('CMS_RATE_LOGIN_MAX', 10), (int) env('CMS_RATE_LOGIN_WINDOW', 60)];
        }
        if (str_starts_with($path, '/install')) {
            return [(int) env('CMS_RATE_INSTALL_MAX', 5), (int) env('CMS_RATE_INSTALL_WINDOW', 300)];
        }
        if (str_starts_with($path, '/admin/modules') || str_starts_with($path, '/admin/routes') || str_starts_with($path, '/admin/settings')) {
            return [(int) env('CMS_RATE_SENSITIVE_MAX', 30), (int) env('CMS_RATE_SENSITIVE_WINDOW', 60)];
        }
        if (str_starts_with($path, '/admin/')) {
            return [(int) env('CMS_RATE_ADMIN_MAX', 90), (int) env('CMS_RATE_ADMIN_WINDOW', 60)];
        }
        return [(int) env('CMS_RATE_DEFAULT_MAX', 120), (int) env('CMS_RATE_DEFAULT_WINDOW', 60)];
    }

    private function normalizePath(string $path): string
    {
        $path = preg_replace('~/\d+(?=/|$)~', '/:id', $path) ?: $path;
        return preg_replace('~/[a-f0-9-]{24,}(?=/|$)~i', '/:token', $path) ?: $path;
    }

    private function garbageCollect(string $directory): void
    {
        if (random_int(1, 100) !== 1) {
            return;
        }
        $threshold = time() - 3600;
        foreach (glob($directory . '/*.json') ?: [] as $file) {
            if (@filemtime($file) !== false && @filemtime($file) < $threshold) {
                @unlink($file);
            }
        }
    }
}
