<?php

namespace Modules\Installer\Services;

final class InstallerStateService
{
    public function isInstalled(): bool
    {
        if (is_file(WRITEPATH . 'cms-installed.lock')) {
            return true;
        }

        if (trim((string) env('database.default.database', '')) === '') {
            return false;
        }

        try {
            $db = db_connect();
            return $db->tableExists('users') && $db->tableExists('cms_modules');
        } catch (\Throwable) {
            return false;
        }
    }

    public function installerEnabled(): bool
    {
        return filter_var(env('CMS_INSTALLER_ENABLED', false), FILTER_VALIDATE_BOOL);
    }

    public function requirements(): array
    {
        $checks = [
            ['name' => 'PHP 8.2+', 'ok' => PHP_VERSION_ID >= 80200, 'detail' => PHP_VERSION],
            ['name' => 'PDO MySQL', 'ok' => extension_loaded('pdo_mysql'), 'detail' => ''],
            ['name' => 'OpenSSL', 'ok' => extension_loaded('openssl'), 'detail' => ''],
            ['name' => 'Intl', 'ok' => extension_loaded('intl'), 'detail' => ''],
            ['name' => 'Mbstring', 'ok' => extension_loaded('mbstring'), 'detail' => ''],
            ['name' => 'Argon2id password hashing', 'ok' => defined('PASSWORD_ARGON2ID'), 'detail' => ''],
            ['name' => 'WRITEPATH доступен на запись', 'ok' => is_writable(WRITEPATH), 'detail' => WRITEPATH],
            ['name' => 'Cache доступен на запись', 'ok' => $this->writableDirectory(WRITEPATH . 'cache'), 'detail' => WRITEPATH . 'cache'],
            ['name' => 'Logs доступны на запись', 'ok' => $this->writableDirectory(WRITEPATH . 'logs'), 'detail' => WRITEPATH . 'logs'],
            ['name' => 'Sessions доступны на запись', 'ok' => $this->writableDirectory(WRITEPATH . 'session'), 'detail' => WRITEPATH . 'session'],
            ['name' => 'Корень проекта доступен для записи .env', 'ok' => is_writable(ROOTPATH) || (is_file(ROOTPATH . '.env') && is_writable(ROOTPATH . '.env')), 'detail' => ROOTPATH],
            ['name' => 'install.sql найден', 'ok' => is_readable(ROOTPATH . 'sql/install.sql'), 'detail' => ROOTPATH . 'sql/install.sql'],
        ];
        return $checks;
    }

    private function writableDirectory(string $path): bool
    {
        if (! is_dir($path)) {
            @mkdir($path, 0770, true);
        }
        return is_dir($path) && is_writable($path);
    }

    public function requirementsOk(): bool
    {
        foreach ($this->requirements() as $check) {
            if (! $check['ok']) {
                return false;
            }
        }
        return true;
    }
}
