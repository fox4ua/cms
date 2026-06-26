<?php

namespace Modules\SystemHealth\Services;

use Modules\Kernel\Services\UpgradeChainValidatorService;
use Modules\Kernel\Services\SchemaVersionService;

final class ReadinessService
{
    public function status(): array
    {
        $checks = [];
        $checks[] = $this->check('installed_marker', is_file(WRITEPATH . 'cms-installed.lock'), 'Файл cms-installed.lock');
        $checks[] = $this->check('maintenance_disabled', ! is_file(WRITEPATH . 'maintenance.flag') && ! filter_var(env('CMS_MAINTENANCE', false), FILTER_VALIDATE_BOOL));
        foreach (['cache', 'logs', 'session'] as $directory) {
            $path = WRITEPATH . $directory;
            $checks[] = $this->check('writable_' . $directory, is_dir($path) && is_writable($path), $path);
        }

        try {
            $db = db_connect();
            $db->query('SELECT 1');
            $checks[] = $this->check('database', true);
            foreach (['users', 'cms_modules', 'cms_routes', 'menus', 'menu_items', 'system_settings'] as $table) {
                $checks[] = $this->check('table_' . $table, $db->tableExists($table));
            }
            if ($db->tableExists('cms_locks')) {
                $activeGlobal = $db->table('cms_locks')
                    ->where('lock_key', 'module.operation.global')
                    ->where('expires_at >=', date('Y-m-d H:i:s'))
                    ->countAllResults() > 0;
                $checks[] = $this->check('no_global_module_lock', ! $activeGlobal, $activeGlobal ? 'Выполняется операция с модулями' : '');
            }
        } catch (\Throwable $e) {
            $checks[] = $this->check('database', false, 'DB unavailable');
        }

        $chain = (new UpgradeChainValidatorService())->validate();
        $checks[] = $this->check('upgrade_chain', $chain['ok'], implode('; ', $chain['errors']));
        $schema = new SchemaVersionService();
        $checks[] = $this->check('schema_version', ! $schema->updateRequired(), (string) $schema->current() . ' / ' . SchemaVersionService::CODE_VERSION);

        $ready = ! array_filter($checks, static fn (array $check): bool => ! $check['ok']);
        return ['ready' => $ready, 'checks' => $checks, 'timestamp' => gmdate(DATE_ATOM)];
    }

    private function check(string $name, bool $ok, string $detail = ''): array
    {
        return ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    }
}
