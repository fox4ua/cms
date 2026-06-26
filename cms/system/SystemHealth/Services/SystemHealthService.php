<?php

namespace Modules\SystemHealth\Services;

use Modules\Kernel\Services\TrustedProxyService;
use Modules\Kernel\Services\UpgradeChainValidatorService;
use Modules\Kernel\Services\SchemaVersionService;

final class SystemHealthService
{
    public function checks(): array
    {
        $items = [];
        $items[] = $this->check('PHP version >= 8.2', PHP_VERSION_ID >= 80200, PHP_VERSION);
        foreach (['intl', 'mbstring', 'openssl', 'pdo_mysql', 'json'] as $ext) {
            $items[] = $this->check('PHP extension: ' . $ext, extension_loaded($ext));
        }

        $proxy = new TrustedProxyService();
        $items[] = $this->check('HTTPS detected', $proxy->isHttps());
        $items[] = $this->check('Trusted proxies configured or direct deployment', $proxy->configured() || empty($_SERVER['HTTP_X_FORWARDED_FOR']), $proxy->configured() ? 'configured' : 'direct');
        $items[] = $this->check('CI_ENVIRONMENT=production', ENVIRONMENT === 'production', ENVIRONMENT);
        $items[] = $this->check('display_errors disabled', ! filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOL), (string) ini_get('display_errors'));
        $items[] = $this->check('expose_php disabled', ! filter_var(ini_get('expose_php'), FILTER_VALIDATE_BOOL), (string) ini_get('expose_php'));
        $items[] = $this->check('PHP session strict mode', (string) ini_get('session.use_strict_mode') === '1', (string) ini_get('session.use_strict_mode'));
        $items[] = $this->check('CMS_APP_KEY задан', strlen((string) env('CMS_APP_KEY')) >= 64);
        $items[] = $this->check('cms.auth.pepper задан', strlen((string) env('cms.auth.pepper')) >= 64);
        $items[] = $this->check('Secure auth cookie', filter_var(env('cms.auth.cookieSecure', true), FILTER_VALIDATE_BOOL));
        $items[] = $this->check('Installer disabled', ! filter_var(env('CMS_INSTALLER_ENABLED', false), FILTER_VALIDATE_BOOL));
        $items[] = $this->check('Rate limiter fails closed', filter_var(env('CMS_RATE_FAIL_CLOSED', true), FILTER_VALIDATE_BOOL));
        $items[] = $this->check('.env is not web-writable', ! is_writable(ROOTPATH . '.env'), ROOTPATH . '.env');
        $items[] = $this->check('System modules are read-only', ! is_writable(ROOTPATH . 'cms/system'), ROOTPATH . 'cms/system');
        $items[] = $this->check('SQL directory is read-only', ! is_writable(ROOTPATH . 'sql'), ROOTPATH . 'sql');
        $items[] = $this->check('Installed marker exists', is_file(WRITEPATH . 'cms-installed.lock'));
        $items[] = $this->check('CMS_ALLOWED_HOSTS задан', trim((string) env('CMS_ALLOWED_HOSTS', '')) !== '');
        $probeToken = trim((string) env('CMS_PROBE_TOKEN', ''));
        $items[] = $this->check('Detailed readiness probe disabled or protected', $probeToken === '' || strlen($probeToken) >= 32, $probeToken === '' ? 'details disabled' : 'protected');
        $items[] = $this->check('Request size limit задан', (int) env('CMS_MAX_REQUEST_MB', 32) > 0);
        $items[] = $this->check('Rate limit storage writable', $this->ensureWritableDirectory(WRITEPATH . 'cache/rate-limit'), WRITEPATH . 'cache/rate-limit');
        $items[] = $this->check('WRITEPATH writable', is_writable(WRITEPATH));
        $items[] = $this->check('Cache writable', is_writable(WRITEPATH . 'cache'));
        $items[] = $this->check('Logs writable', is_writable(WRITEPATH . 'logs'));
        $items[] = $this->check('Maintenance flag absent', ! is_file(WRITEPATH . 'maintenance.flag'));

        try {
            $db = db_connect();
            $db->query('SELECT 1');
            $items[] = $this->check('DB connection', true);
            foreach (['cms_routes', 'cms_modules', 'cms_locks', 'module_operation_logs', 'menus', 'menu_items'] as $table) {
                $items[] = $this->check('DB table: ' . $table, $db->tableExists($table));
            }
        } catch (\Throwable) {
            $items[] = $this->check('DB connection', false);
        }

        $chain = (new UpgradeChainValidatorService())->validate();
        $items[] = $this->check('Upgrade chain valid', $chain['ok'], $chain['ok'] ? $chain['count'] . ' update files' : implode('; ', $chain['errors']));
        $schema = new SchemaVersionService();
        $currentSchema = $schema->current();
        $items[] = $this->check('Schema version current', ! $schema->updateRequired(), (string) $currentSchema . ' / ' . SchemaVersionService::CODE_VERSION);

        return $items;
    }

    public function readiness(): array
    {
        return (new ReadinessService())->status();
    }

    public function productionScore(): array
    {
        $checks = $this->checks();
        $passed = count(array_filter($checks, static fn (array $item): bool => $item['ok']));
        $total = count($checks);
        return [
            'passed' => $passed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($passed / $total * 100) : 0,
            'ready' => $passed === $total,
        ];
    }

    private function check(string $name, bool $ok, string $detail = ''): array
    {
        return ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    }

    private function ensureWritableDirectory(string $path): bool
    {
        if (! is_dir($path)) {
            @mkdir($path, 0770, true);
        }
        return is_dir($path) && is_writable($path);
    }
}
