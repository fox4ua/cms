<?php

namespace Modules\Installer\Services;

use PDO;
use RuntimeException;
use Throwable;

final class InstallerService
{
    public function install(array $input): void
    {
        $lockPath = WRITEPATH . 'cms-installer-operation.lock';
        $lock = @fopen($lockPath, 'c+');
        if ($lock === false || ! @flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            throw new RuntimeException('Установка уже выполняется в другом запросе.');
        }

        try {
            $this->performInstall($input);
        } finally {
            @flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($lockPath);
        }
    }

    private function performInstall(array $input): void
    {
        $state = new InstallerStateService();
        if ($state->isInstalled()) {
            throw new RuntimeException('CMS уже установлена.');
        }
        if (! $state->installerEnabled()) {
            throw new RuntimeException('Веб-установщик отключён. Укажите CMS_INSTALLER_ENABLED=true в .env.');
        }
        if (! $state->requirementsOk()) {
            throw new RuntimeException('Не выполнены системные требования установщика.');
        }

        $data = $this->validate($input);
        $pdo = $this->connect($data);
        $this->assertEmptyDatabase($pdo);

        $markerPath = WRITEPATH . 'cms-installed.lock';
        try {
            $sql = file_get_contents(ROOTPATH . 'sql/install.sql');
            if ($sql === false || trim($sql) === '') {
                throw new RuntimeException('Файл sql/install.sql пуст или недоступен.');
            }

            foreach ((new \Modules\Kernel\Services\SqlStatementParser())->parse($sql) as $statement) {
                $pdo->exec($statement);
            }

            $this->createAdministrator($pdo, $data['admin_email'], $data['admin_password']);
            $stmt = $pdo->prepare('UPDATE system_settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = :key');
            $stmt->execute(['value' => $data['admin_email'], 'key' => 'admin_email']);
            $stmt->execute(['value' => $data['site_name'], 'key' => 'site_name']);

            $marker = json_encode([
                'installed_at' => gmdate(DATE_ATOM),
                'schema_version' => \Modules\Kernel\Services\SchemaVersionService::CODE_VERSION,
                'base_url' => $data['base_url'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($marker === false || file_put_contents($markerPath, $marker . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('Не удалось создать маркер установленной CMS.');
            }
            @chmod($markerPath, 0600);

            $https = str_starts_with(strtolower($data['base_url']), 'https://');
            (new EnvironmentWriterService())->write([
                'CI_ENVIRONMENT' => 'production',
                'app.baseURL' => rtrim($data['base_url'], '/') . '/',
                'app.forceGlobalSecureRequests' => $https ? 'true' : 'false',
                'database.default.hostname' => $data['db_host'],
                'database.default.database' => $data['db_name'],
                'database.default.username' => $data['db_user'],
                'database.default.password' => $data['db_password'],
                'database.default.DBDriver' => 'MySQLi',
                'database.default.port' => (string) $data['db_port'],
                'CMS_APP_KEY' => bin2hex(random_bytes(32)),
                'cms.auth.pepper' => bin2hex(random_bytes(64)),
                'cms.auth.cookieSecure' => $https ? 'true' : 'false',
                'cookie.secure' => $https ? 'true' : 'false',
                'cookie.httponly' => 'true',
                'cookie.samesite' => 'Lax',
                'CMS_FORCE_HTTPS' => $https ? 'true' : 'false',
                'CMS_ALLOWED_HOSTS' => (string) parse_url($data['base_url'], PHP_URL_HOST),
                'CMS_PROBE_TOKEN' => bin2hex(random_bytes(32)),
                'CMS_INSTALLER_ENABLED' => 'false',
            ]);
        } catch (Throwable $e) {
            @unlink($markerPath);
            $this->cleanupFailedInstallation($pdo);
            $errorId = strtoupper(substr(hash('sha256', get_class($e) . '|' . $e->getMessage() . '|' . microtime(true)), 0, 12));
            log_message('critical', 'Installer failed [' . $errorId . ']: ' . $e->getMessage());
            throw new RuntimeException('Установка не завершена. База возвращена в исходное пустое состояние. Код ошибки: ' . $errorId, 0, $e);
        }
    }

    private function connect(array $data): PDO
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $data['db_host'], $data['db_port'], $data['db_name']);
            return new PDO($dsn, $data['db_user'], $data['db_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Throwable $e) {
            $errorId = strtoupper(substr(hash('sha256', get_class($e) . '|' . $e->getMessage() . '|' . microtime(true)), 0, 12));
            log_message('error', 'Installer database connection failed [' . $errorId . ']: ' . $e->getMessage());
            throw new RuntimeException('Не удалось подключиться к базе данных. Проверьте параметры. Код ошибки: ' . $errorId, 0, $e);
        }
    }

    private function validate(array $input): array
    {
        $data = [
            'site_name' => trim((string) ($input['site_name'] ?? 'CMS')),
            'base_url' => rtrim(trim((string) ($input['base_url'] ?? '')), '/') . '/',
            'db_host' => trim((string) ($input['db_host'] ?? 'localhost')),
            'db_port' => (int) ($input['db_port'] ?? 3306),
            'db_name' => trim((string) ($input['db_name'] ?? '')),
            'db_user' => trim((string) ($input['db_user'] ?? '')),
            'db_password' => (string) ($input['db_password'] ?? ''),
            'admin_email' => mb_strtolower(trim((string) ($input['admin_email'] ?? ''))),
            'admin_password' => (string) ($input['admin_password'] ?? ''),
            'admin_password_confirm' => (string) ($input['admin_password_confirm'] ?? ''),
        ];

        if ($data['site_name'] === '' || mb_strlen($data['site_name']) > 190) {
            throw new RuntimeException('Некорректное название сайта.');
        }

        $parts = parse_url($data['base_url']);
        if (! filter_var($data['base_url'], FILTER_VALIDATE_URL)
            || ! is_array($parts)
            || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'], $parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            throw new RuntimeException('Некорректный base URL. Используйте URL без логина, query и fragment.');
        }

        if ($data['db_host'] === '' || preg_match('/[;=\r\n\0]/', $data['db_host'])) {
            throw new RuntimeException('Некорректный хост базы данных.');
        }
        if (! preg_match('/^[A-Za-z0-9_$-]{1,64}$/', $data['db_name'])) {
            throw new RuntimeException('Некорректное имя базы данных.');
        }
        if ($data['db_user'] === '' || preg_match('/[;\r\n\0]/', $data['db_user']) || $data['db_port'] < 1 || $data['db_port'] > 65535) {
            throw new RuntimeException('Некорректные параметры подключения к БД.');
        }
        if (! filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Некорректный email администратора.');
        }

        $password = $data['admin_password'];
        if (! hash_equals($password, $data['admin_password_confirm'])) {
            throw new RuntimeException('Пароли администратора не совпадают.');
        }
        if (strlen($password) < 14
            || ! preg_match('/[A-Z]/', $password)
            || ! preg_match('/[a-z]/', $password)
            || ! preg_match('/\d/', $password)
            || ! preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new RuntimeException('Пароль администратора: минимум 14 символов, верхний и нижний регистр, цифра и спецсимвол.');
        }

        return $data;
    }

    private function assertEmptyDatabase(PDO $pdo): void
    {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE IN ('BASE TABLE','VIEW')")->fetchColumn();
        if ($count !== 0) {
            throw new RuntimeException('Для установки требуется полностью пустая база данных.');
        }
    }

    private function cleanupFailedInstallation(PDO $pdo): void
    {
        try {
            $rows = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach ($rows as $row) {
                $name = str_replace('`', '``', (string) ($row[0] ?? ''));
                if ($name === '') {
                    continue;
                }
                $type = strtoupper((string) ($row[1] ?? 'BASE TABLE'));
                $pdo->exec(($type === 'VIEW' ? 'DROP VIEW IF EXISTS `' : 'DROP TABLE IF EXISTS `') . $name . '`');
            }
        } catch (Throwable $cleanupError) {
            log_message('critical', 'Installer cleanup failed: ' . $cleanupError->getMessage());
        } finally {
            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            } catch (Throwable) {
            }
        }
    }

    private function createAdministrator(PDO $pdo, string $email, string $password): void
    {
        $id = $this->uuidV4();
        $hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
        if ($hash === false) {
            throw new RuntimeException('Не удалось создать хеш пароля администратора.');
        }
        $stmt = $pdo->prepare("INSERT INTO users (id,email,password_hash,status,password_changed_at,created_at) VALUES (:id,:email,:hash,'active',NOW(),NOW())");
        $stmt->execute(['id' => $id, 'email' => $email, 'hash' => $hash]);
        $stmt = $pdo->prepare('INSERT INTO user_security_flags (user_id,force_password_change,password_changed_at,updated_at) VALUES (:id,0,NOW(),NOW())');
        $stmt->execute(['id' => $id]);
        $stmt = $pdo->prepare('INSERT INTO user_password_history (user_id,password_hash,created_at) VALUES (:id,:hash,NOW())');
        $stmt->execute(['id' => $id, 'hash' => $hash]);
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
