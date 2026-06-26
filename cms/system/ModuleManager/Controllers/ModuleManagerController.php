<?php

namespace Modules\ModuleManager\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\Kernel\Services\AdminActionLoggerService;
use Modules\Kernel\Services\CmsCacheService;
use Modules\ModuleManager\Models\CmsModuleModel;
use Modules\ModuleManager\Models\ModuleOperationLogModel;
use Modules\ModuleManager\Services\ModuleInstallerService;
use Modules\ModuleManager\Services\ModuleLockService;
use Modules\ModuleManager\Services\ModuleOperationLoggerService;
use Modules\ModuleManager\Services\ModulePreflightService;
use Modules\ModuleManager\Services\ModuleRegistryService;
use Throwable;

final class ModuleManagerController extends AdminController
{
    public function index()
    {
        (new ModuleRegistryService())->sync();
        return $this->render('Modules\ModuleManager\Views\index', [
            'pageTitle' => 'Менеджер модулей',
            'modules' => (new CmsModuleModel())->orderBy('menu_order', 'ASC')->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function preflight($id, string $operation = 'install')
    {
        $operation = in_array($operation, ['install', 'update'], true) ? $operation : 'install';
        return $this->render('Modules\ModuleManager\Views\preflight', [
            'pageTitle' => 'Проверка модуля',
            'operation' => $operation,
            'result' => (new ModulePreflightService())->check((int) $id, $operation),
        ]);
    }

    public function operations()
    {
        return $this->render('Modules\ModuleManager\Views\operations', [
            'pageTitle' => 'Журнал операций модулей',
            'operations' => (new ModuleOperationLogModel())->orderBy('id', 'DESC')->findAll(200),
        ]);
    }

    public function locks()
    {
        return $this->render('Modules\ModuleManager\Views\locks', [
            'pageTitle' => 'Блокировки модулей',
            'locks' => (new ModuleLockService())->active(),
        ]);
    }

    public function forceUnlock(string $key)
    {
        if (! $this->request->is('post')) {
            return redirect()->to('/admin/modules/locks')->with('error', 'Недопустимый метод запроса.');
        }
        $key = rawurldecode($key);
        if (! preg_match('/^[A-Za-z0-9._:-]{1,190}$/', $key)) {
            return redirect()->to('/admin/modules/locks')->with('error', 'Некорректный ключ блокировки.');
        }
        $deleted = (new ModuleLockService())->forceRelease($key);
        (new AdminActionLoggerService())->log('module.lock.force_release', 'lock', $key);
        return redirect()->to('/admin/modules/locks')->with($deleted ? 'success' : 'error', $deleted ? 'Блокировка снята.' : 'Блокировка не найдена.');
    }

    public function install($id) { return $this->runAction('install', (int) $id, 'Модуль установлен'); }
    public function update($id)  { return $this->runAction('update', (int) $id, 'Модуль обновлён'); }
    public function enable($id)  { return $this->runAction('enable', (int) $id, 'Модуль включён'); }
    public function disable($id) { return $this->runAction('disable', (int) $id, 'Модуль выключен'); }

    public function sync()
    {
        $logger = new ModuleOperationLoggerService();
        $operation = $logger->start('ModuleManager', 'sync');
        try {
            $count = (new ModuleRegistryService())->sync();
            (new CmsCacheService())->clearKernel();
            (new AdminActionLoggerService())->log('module.sync');
            $logger->success($operation, 'Синхронизировано модулей: ' . $count);
            return redirect()->to('/admin/modules')->with('success', 'Синхронизировано модулей: ' . $count);
        } catch (Throwable $e) {
            $logger->failure($operation, $e);
            return redirect()->to('/admin/modules')->with('error', $e->getMessage());
        }
    }

    private function runAction(string $method, int $id, string $success)
    {
        try {
            (new ModuleInstallerService())->{$method}($id);
            return redirect()->to('/admin/modules')->with('success', $success);
        } catch (Throwable $e) {
            return redirect()->to('/admin/modules')->with('error', $e->getMessage());
        }
    }
}
