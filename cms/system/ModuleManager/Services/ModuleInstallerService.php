<?php

namespace Modules\ModuleManager\Services;

use Modules\Auth\Services\AuthExtensionRegistryService;
use Modules\Kernel\Services\AdminActionLoggerService;
use Modules\Kernel\Services\CmsCacheService;
use Modules\Kernel\Services\ModuleAccessService;
use Modules\Kernel\Services\ModulePathService;
use Modules\ModuleManager\Models\CmsModuleModel;
use Modules\ModuleManager\Models\CmsModuleUpdateModel;
use Modules\RouteManager\Services\RouteRegistryService;
use RuntimeException;
use Throwable;

final class ModuleInstallerService
{
    private CmsModuleModel $modules;
    private ModuleRegistryService $registry;
    private ModuleSqlRunnerService $sql;
    private ModuleMenuService $menu;
    private CmsCacheService $cache;
    private ModuleOperationLoggerService $operations;
    private ?RouteRegistryService $routes = null;
    private ?AuthExtensionRegistryService $authHooks = null;

    public function __construct()
    {
        $this->modules = new CmsModuleModel();
        $this->registry = new ModuleRegistryService();
        $this->sql = new ModuleSqlRunnerService();
        $this->menu = new ModuleMenuService();
        $this->cache = new CmsCacheService();
        $this->operations = new ModuleOperationLoggerService();
        $this->routes = class_exists(RouteRegistryService::class) ? new RouteRegistryService() : null;
        $this->authHooks = class_exists(AuthExtensionRegistryService::class) ? new AuthExtensionRegistryService() : null;
    }

    public function install(int $id): void
    {
        $module = $this->mustFind($id);
        $to = (string) ($this->registry->meta($module['machine_name'])['version'] ?? $module['available_version'] ?? '1.0.0');
        $this->runLogged($module, 'install', '0.0.0', $to, fn () => $this->doInstall($module));
    }

    public function update(int $id): void
    {
        $module = $this->mustFind($id);
        $meta = $this->registry->meta($module['machine_name']);
        $from = (string) ($module['installed_version'] ?: $module['version'] ?: '0.0.0');
        $to = (string) ($meta['version'] ?? $module['available_version'] ?? $from);
        $this->runLogged($module, 'update', $from, $to, fn () => $this->doUpdate($module, $meta, $from, $to));
    }

    public function enable(int $id): void
    {
        $module = $this->mustFind($id);
        $version = (string) ($module['installed_version'] ?: $module['version']);
        $this->runLogged($module, 'enable', $version, $version, fn () => $this->doEnable($module));
    }

    public function disable(int $id): void
    {
        $module = $this->mustFind($id);
        $version = (string) ($module['installed_version'] ?: $module['version']);
        $this->runLogged($module, 'disable', $version, $version, fn () => $this->doDisable($module));
    }

    private function doInstall(array $module): void
    {
        $lockKey = 'module.install.' . $module['machine_name'];
        $lock = new ModuleLockService();
        $lock->acquire('module.operation.global', 600, 'install:' . $module['machine_name']);
        $lock->acquire($lockKey, 600, 'install:' . $module['machine_name']);
        try {
            if ((int) ($module['is_installed'] ?? 0) === 1) {
                throw new RuntimeException('Модуль уже установлен.');
            }
            $meta = $this->registry->meta($module['machine_name']);
            $this->assertPreflightOk((int) $module['id'], 'install');
            $this->checkDependencies($meta);

            $db = db_connect();
            $db->transBegin();
            try {
                $this->modules->update($module['id'], ['install_status' => 'installing', 'last_error' => null, 'updated_at' => date('Y-m-d H:i:s')]);
                foreach ((array) ($meta['install_sql'] ?? []) as $relativeFile) {
                    $file = (new ModulePathService())->moduleFile($module['machine_name'], (string) $relativeFile) ?? '';
                    $this->sql->runFile($file);
                    $this->logUpdate($module['machine_name'], '0.0.0', (string) ($meta['version'] ?? '1.0.0'), $file, 'success');
                }
                $version = (string) ($meta['version'] ?? $module['available_version'] ?? '1.0.0');
                $this->syncIntegration($module['machine_name'], $meta, true);
                $this->modules->update($module['id'], [
                    'version' => $version,
                    'available_version' => $version,
                    'installed_version' => $version,
                    'install_status' => 'installed',
                    'is_installed' => 1,
                    'is_enabled' => 1,
                    'installed_at' => $module['installed_at'] ?: date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                if (! $db->transStatus()) {
                    throw new RuntimeException('Ошибка транзакции установки модуля.');
                }
                $db->transCommit();
                $this->cache->clearKernel();
                (new AdminActionLoggerService())->log('module.install', 'module', $module['machine_name']);
            } catch (Throwable $e) {
                $db->transRollback();
                $this->modules->update($module['id'], ['install_status' => 'error', 'last_error' => $e->getMessage(), 'updated_at' => date('Y-m-d H:i:s')]);
                $this->logUpdate($module['machine_name'], '0.0.0', (string) ($meta['version'] ?? '1.0.0'), '', 'error', $e->getMessage());
                throw $e;
            }
        } finally {
            $lock->release($lockKey);
            $lock->release('module.operation.global');
        }
    }

    private function doUpdate(array $module, array $meta, string $from, string $to): void
    {
        $lockKey = 'module.update.' . $module['machine_name'];
        $lock = new ModuleLockService();
        $lock->acquire('module.operation.global', 900, 'update:' . $module['machine_name']);
        $lock->acquire($lockKey, 900, 'update:' . $module['machine_name']);
        try {
            if ((int) $module['is_installed'] !== 1) {
                throw new RuntimeException('Модуль ещё не установлен.');
            }
            $this->assertPreflightOk((int) $module['id'], 'update');
            $this->checkDependencies($meta);
            if (version_compare($to, $from, '<=')) {
                return;
            }

            $db = db_connect();
            $db->transBegin();
            try {
                $this->modules->update($module['id'], ['install_status' => 'updating', 'last_error' => null, 'updated_at' => date('Y-m-d H:i:s')]);
                foreach ($this->updatesToRun($meta, $from, $to, $module['machine_name']) as $version => $file) {
                    if ($this->wasUpdateExecuted($module['machine_name'], $version, $file)) {
                        continue;
                    }
                    $this->sql->runFile($file);
                    $this->logUpdate($module['machine_name'], $from, $version, $file, 'success');
                }
                $this->syncIntegration($module['machine_name'], $meta, true);
                $this->modules->update($module['id'], [
                    'version' => $to,
                    'available_version' => $to,
                    'installed_version' => $to,
                    'install_status' => 'installed',
                    'is_installed' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                if (! $db->transStatus()) {
                    throw new RuntimeException('Ошибка транзакции обновления модуля.');
                }
                $db->transCommit();
                $this->cache->clearKernel();
                (new AdminActionLoggerService())->log('module.update', 'module', $module['machine_name']);
            } catch (Throwable $e) {
                $db->transRollback();
                $this->modules->update($module['id'], ['install_status' => 'error', 'last_error' => $e->getMessage(), 'updated_at' => date('Y-m-d H:i:s')]);
                $this->logUpdate($module['machine_name'], $from, $to, '', 'error', $e->getMessage());
                throw $e;
            }
        } finally {
            $lock->release($lockKey);
            $lock->release('module.operation.global');
        }
    }

    private function doEnable(array $module): void
    {
        $lockKey = 'module.enable.' . $module['machine_name'];
        $lock = new ModuleLockService();
        $lock->acquire('module.operation.global', 300, 'enable:' . $module['machine_name']);
        $lock->acquire($lockKey, 300, 'enable:' . $module['machine_name']);
        try {
            if ((int) $module['is_installed'] !== 1) {
                throw new RuntimeException('Нельзя включить неустановленный модуль.');
            }
            $meta = $this->registry->meta($module['machine_name']);
            $this->checkDependencies($meta);
            $db = db_connect();
            $db->transBegin();
            try {
                $this->syncIntegration($module['machine_name'], $meta, true);
                $this->modules->update($module['id'], ['is_enabled' => 1, 'install_status' => 'installed', 'updated_at' => date('Y-m-d H:i:s')]);
                $db->transCommit();
                $this->cache->clearKernel();
                (new AdminActionLoggerService())->log('module.enable', 'module', $module['machine_name']);
            } catch (Throwable $e) {
                $db->transRollback();
                throw $e;
            }
        } finally {
            $lock->release($lockKey);
            $lock->release('module.operation.global');
        }
    }

    private function doDisable(array $module): void
    {
        $lockKey = 'module.disable.' . $module['machine_name'];
        $lock = new ModuleLockService();
        $lock->acquire('module.operation.global', 300, 'disable:' . $module['machine_name']);
        $lock->acquire($lockKey, 300, 'disable:' . $module['machine_name']);
        try {
            if ((int) $module['is_system'] === 1 || (new ModuleAccessService())->isSystemModule($module['machine_name'])) {
                throw new RuntimeException('Системный модуль нельзя отключить.');
            }
            $this->ensureNoEnabledDependents($module['machine_name']);
            $db = db_connect();
            $db->transBegin();
            try {
                $this->menu->setModuleMenuActive($module['machine_name'], false);
                $this->routes?->setModuleRoutesActive($module['machine_name'], false);
                $this->authHooks?->setModuleHooksActive($module['machine_name'], false);
                $this->modules->update($module['id'], ['is_enabled' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
                $db->transCommit();
                $this->cache->clearKernel();
                (new AdminActionLoggerService())->log('module.disable', 'module', $module['machine_name']);
            } catch (Throwable $e) {
                $db->transRollback();
                throw $e;
            }
        } finally {
            $lock->release($lockKey);
            $lock->release('module.operation.global');
        }
    }

    private function runLogged(array $module, string $operation, ?string $from, ?string $to, callable $callback): void
    {
        $log = $this->operations->start($module['machine_name'], $operation, $from, $to);
        try {
            $callback();
            $this->operations->success($log, 'Операция завершена успешно.');
        } catch (Throwable $e) {
            $this->operations->failure($log, $e);
            throw $e;
        }
    }

    private function syncIntegration(string $machine, array $meta, bool $active): void
    {
        $this->menu->syncMenu($machine, $meta);
        $this->menu->setModuleMenuActive($machine, $active);
        $this->routes?->syncModuleRoutes($machine, $meta, $active);
        $this->authHooks?->syncModuleHooks($machine, $meta, $active);
    }

    private function assertPreflightOk(int $id, string $operation): void
    {
        $result = (new ModulePreflightService())->check($id, $operation);
        if (! $result['can_continue']) {
            throw new RuntimeException('Preflight-проверка не пройдена. Откройте страницу проверки модуля.');
        }
    }

    private function ensureNoEnabledDependents(string $machine): void
    {
        foreach ($this->modules->where('is_installed', 1)->where('is_enabled', 1)->findAll() as $row) {
            $deps = json_decode((string) ($row['dependencies'] ?? '{}'), true) ?: [];
            if (array_key_exists($machine, $deps)) {
                throw new RuntimeException('Нельзя отключить модуль: от него зависит включённый модуль ' . $row['machine_name']);
            }
        }
    }

    private function updatesToRun(array $meta, string $from, string $to, string $machine): array
    {
        $files = [];
        foreach ((array) ($meta['update_sql'] ?? []) as $version => $relativeFile) {
            if (version_compare((string) $version, $from, '>') && version_compare((string) $version, $to, '<=')) {
                $files[(string) $version] = (new ModulePathService())->moduleFile($machine, (string) $relativeFile) ?? '';
            }
        }
        uksort($files, 'version_compare');
        return $files;
    }

    private function wasUpdateExecuted(string $module, string $toVersion, string $file): bool
    {
        return (new CmsModuleUpdateModel())->where('module', $module)->where('to_version', $toVersion)->where('sql_file', $file)->where('status', 'success')->first() !== null;
    }

    private function logUpdate(string $module, string $from, string $to, string $file, string $status, ?string $error = null): void
    {
        try {
            (new CmsModuleUpdateModel())->insert([
                'module' => $module,
                'from_version' => $from,
                'to_version' => $to,
                'sql_file' => $file,
                'status' => $status,
                'error' => $error,
                'executed_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
        }
    }

    private function checkDependencies(array $meta): void
    {
        foreach ((array) ($meta['dependencies'] ?? []) as $dependency => $minVersion) {
            $row = $this->modules->where('machine_name', (string) $dependency)->first();
            if (! $row || (int) $row['is_installed'] !== 1 || (int) $row['is_enabled'] !== 1) {
                throw new RuntimeException('Не установлена зависимость: ' . $dependency);
            }
            if ($minVersion && version_compare((string) ($row['installed_version'] ?? $row['version']), (string) $minVersion, '<')) {
                throw new RuntimeException('Зависимость ' . $dependency . ' должна быть версии не ниже ' . $minVersion);
            }
        }
    }

    private function mustFind(int $id): array
    {
        $row = $this->modules->find($id);
        if (! $row) {
            throw new RuntimeException('Модуль не найден.');
        }
        return $row;
    }
}
