<?php

namespace Modules\ModuleManager\Services;

use Modules\ModuleManager\Models\CmsModuleModel;
use Modules\Kernel\Services\ModulePathService;
use Modules\RouteManager\Services\RouteRegistryService;
use Modules\RouteManager\Services\RouteValidationService;

class ModulePreflightService
{
    public function check(int $id, string $operation): array
    {
        $module = (new CmsModuleModel())->find($id);
        if (! $module) {
            return $this->result(null, [['level' => 'error', 'message' => 'Модуль не найден.']]);
        }

        $checks = [];
        $machine = (string) $module['machine_name'];
        $paths = new ModulePathService();
        $base = $paths->moduleBase($machine);
        $manifest = $paths->manifest($machine);

        $this->add($checks, $manifest !== null && is_file($manifest), 'module.php найден', 'module.php отсутствует');
        $meta = [];
        if ($manifest !== null && is_file($manifest)) {
            $meta = require $manifest;
            $this->add($checks, is_array($meta), 'module.php корректно возвращает массив', 'module.php должен возвращать массив');
        }

        $version = (string) ($meta['version'] ?? '');
        $this->add($checks, (bool) preg_match('/^\d+\.\d+\.\d+([-.][A-Za-z0-9]+)?$/', $version), 'Версия модуля корректна: ' . $version, 'Некорректная версия модуля');

        $coreMin = (string) ($meta['core_min_version'] ?? '');
        $this->add($checks, $coreMin === '' || version_compare(CI_VERSION, $coreMin, '>='), 'Совместимость с CodeIgniter/Kernel пройдена', 'Требуется CodeIgniter/Kernel не ниже ' . $coreMin);

        foreach ((array) ($meta['dependencies'] ?? []) as $dependency => $minVersion) {
            $row = (new CmsModuleModel())->where('machine_name', (string) $dependency)->first();
            $ok = $row && (int) $row['is_installed'] === 1 && (int) $row['is_enabled'] === 1;
            if ($ok && $minVersion) {
                $ok = version_compare((string) ($row['installed_version'] ?? $row['version']), (string) $minVersion, '>=');
            }
            $this->add($checks, $ok, 'Зависимость ' . $dependency . ' установлена', 'Не выполнена зависимость: ' . $dependency);
        }

        $sqlFiles = [];
        if ($operation === 'install') {
            foreach ((array) ($meta['install_sql'] ?? []) as $relative) {
                $sqlFiles[] = $paths->moduleFile($machine, (string) $relative) ?? '';
            }
        } elseif ($operation === 'update') {
            $from = (string) ($module['installed_version'] ?: $module['version'] ?: '0.0.0');
            $to = (string) ($meta['version'] ?? $module['available_version'] ?? $from);
            foreach ((array) ($meta['update_sql'] ?? []) as $ver => $relative) {
                if (version_compare((string) $ver, $from, '>') && version_compare((string) $ver, $to, '<=')) {
                    $sqlFiles[] = $paths->moduleFile($machine, (string) $relative) ?? '';
                }
            }
            $this->add($checks, version_compare($to, $from, '>'), 'Есть обновление ' . $from . ' → ' . $to, 'Доступная версия не выше установленной', 'warn');
        }

        $runner = new ModuleSqlRunnerService();
        foreach ($sqlFiles as $file) {
            $this->add($checks, is_file($file), 'SQL-файл найден: ' . basename($file), 'SQL-файл не найден: ' . basename($file));
            if (is_file($file)) {
                $err = $runner->validateFile($file);
                $this->add($checks, $err === null, 'SQL-файл прошёл проверку: ' . basename($file), $err ?: 'SQL-файл не прошёл проверку');
                $sql = file_get_contents($file) ?: '';
                foreach ($this->extractCreateTables($sql) as $table) {
                    $exists = db_connect()->tableExists($table);
                    $ok = $operation === 'update' || ! $exists;
                    $this->add($checks, $ok, 'Конфликтов таблицы нет: ' . $table, 'Таблица уже существует: ' . $table);
                }
            }
        }

        foreach ((array) ($meta['routes'] ?? []) as $route) {
            if (is_array($route)) {
                $method = strtoupper((string)($route['method'] ?? 'GET'));
                $path = (string)($route['path'] ?? '');
                $routeData = [
                    'module' => $machine,
                    'route_key' => (string)($route['key'] ?? ($machine . ':' . $method . ':' . $path)),
                    'http_method' => $method,
                    'path' => $path,
                    'controller' => (string)($route['controller'] ?? ''),
                    'action' => (string)($route['action'] ?? 'index'),
                    'is_admin' => (int)($route['is_admin'] ?? str_starts_with($path, '/admin/')),
                    'is_active' => 1,
                    'is_system' => (int)($route['is_system'] ?? ($meta['is_system'] ?? 0)),
                    'sort_order' => (int)($route['sort_order'] ?? 100),
                ];
                if (class_exists(RouteValidationService::class)) {
                    $errors = (new RouteValidationService())->validate((new RouteValidationService())->normalize($routeData));
                    $this->add($checks, empty($errors), 'Маршрут прошёл строгую проверку: ' . $method . ' ' . $path, 'Ошибка маршрута ' . $method . ' ' . $path . ': ' . implode('; ', $errors));
                }
                $controller = (string)$routeData['controller'];
                $action = (string)$routeData['action'];
                $methodName = preg_replace('/\/.*/', '', $action);
                $this->add($checks, $controller !== '' && class_exists($controller), 'Контроллер маршрута найден: ' . $controller, 'Контроллер маршрута не найден: ' . $controller);
                $this->add($checks, $controller !== '' && class_exists($controller) && method_exists($controller, $methodName), 'Action маршрута найден: ' . $methodName, 'Action маршрута не найден: ' . $methodName);
                if (class_exists(RouteRegistryService::class)) {
                    $conflict = (new RouteRegistryService())->hasRouteConflict($method, $path, $machine);
                    $this->add($checks, $conflict === null, 'Конфликтов маршрута нет: ' . $method . ' ' . $path, 'Маршрут уже занят модулем: ' . ($conflict['module'] ?? 'unknown'));
                }
            }
        }

        foreach ((array) ($meta['menu'] ?? []) as $item) {
            $url = (string) ($item['url'] ?? '');
            $this->add($checks, $url === '' || str_starts_with($url, '/admin/'), 'Пункт меню допустим: ' . $url, 'Недопустимый пункт меню: ' . $url, 'warn');
        }

        $this->add($checks, is_writable(WRITEPATH . 'cache'), 'Папка cache доступна на запись', 'Папка cache недоступна на запись', 'warn');
        $this->add($checks, PHP_VERSION_ID >= 80200, 'PHP версия подходит: ' . PHP_VERSION, 'Рекомендуется PHP 8.2+', 'warn');
        foreach (['intl', 'mbstring', 'openssl'] as $ext) {
            $this->add($checks, extension_loaded($ext), 'PHP extension ' . $ext . ' загружен', 'PHP extension ' . $ext . ' не найден', 'warn');
        }
        $this->add($checks, true, 'Перед обновлением сделайте backup средствами панели/сервера. CMS backup не выполняет.', '', 'warn');

        return $this->result($module, $checks, $meta);
    }

    private function extractCreateTables(string $sql): array
    {
        preg_match_all('/CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m);
        return array_values(array_unique($m[1] ?? []));
    }

    private function add(array &$checks, bool $ok, string $success, string $fail, string $failLevel = 'error'): void
    {
        $checks[] = ['level' => $ok ? 'ok' : $failLevel, 'message' => $ok ? $success : $fail];
    }

    private function result(?array $module, array $checks, array $meta = []): array
    {
        $hasErrors = count(array_filter($checks, static fn ($c) => $c['level'] === 'error')) > 0;
        return ['module' => $module, 'meta' => $meta, 'checks' => $checks, 'can_continue' => ! $hasErrors];
    }
}
