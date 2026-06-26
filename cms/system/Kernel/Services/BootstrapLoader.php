<?php

namespace Modules\Kernel\Services;

use Throwable;

final class BootstrapLoader
{
    public static function applyRequestGuards(bool $allowDatabaseSettings = true): void
    {
        (new SecurityHeaderService())->apply($allowDatabaseSettings);
        (new HostGuardService())->assertAllowed();
        (new RequestSizeLimiter())->assertAllowed();
        (new RateLimitService())->enforce();
    }

    public static function boot(): BootstrapStatus
    {
        self::applyRequestGuards();

        if (filter_var(env('CMS_MAINTENANCE', false), FILTER_VALIDATE_BOOL) || is_file(WRITEPATH . 'maintenance.flag')) {
            return new BootstrapStatus(true, false, 'MAINT-001', 'CMS находится в режиме обслуживания.');
        }

        $hasDatabaseConfiguration = trim((string) env('database.default.database', '')) !== '';
        $installedMarker = is_file(WRITEPATH . 'cms-installed.lock');

        if (! $hasDatabaseConfiguration && ! $installedMarker) {
            return new BootstrapStatus(false, false, 'INSTALL', 'Требуется установка CMS.', true);
        }

        try {
            $db = db_connect();
            $db->initialize();
            $db->query('SELECT 1');
        } catch (Throwable $e) {
            log_message('critical', 'Bootstrap database unavailable: ' . $e->getMessage());
            return new BootstrapStatus(false, false, 'DB-001', 'Нет соединения с базой данных.');
        }

        try {
            if (! $db->tableExists('cms_modules') || ! $db->tableExists('users')) {
                return new BootstrapStatus(false, true, 'INSTALL', 'Структура CMS не установлена.', true);
            }
            if (! $installedMarker && is_writable(WRITEPATH)) {
                @file_put_contents(WRITEPATH . 'cms-installed.lock', json_encode(['installed_at' => gmdate(DATE_ATOM), 'schema_version' => (new SchemaVersionService())->current() ?: 'legacy']) . PHP_EOL, LOCK_EX);
                @chmod(WRITEPATH . 'cms-installed.lock', 0600);
            }
            if ((new SchemaVersionService())->updateRequired()) {
                return new BootstrapStatus(false, true, 'UPDATE', 'Требуется обновление структуры CMS.', false, true);
            }
        } catch (Throwable $e) {
            log_message('critical', 'Bootstrap schema check failed: ' . $e->getMessage());
            return new BootstrapStatus(false, false, 'DB-002', 'Не удалось проверить структуру базы данных.');
        }

        return new BootstrapStatus(false, true, null, null);
    }
}
