<?php

namespace Modules\Maintenance\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Kernel\Controllers\AdminController;
use Modules\Kernel\Services\AdminActionLoggerService;
use Modules\Maintenance\Services\CleanupRegistryService;

final class MaintenanceController extends AdminController
{
    public function index()
    {
        return $this->render('Modules\Maintenance\Views\index', [
            'pageTitle' => 'Обслуживание CMS',
            'providers' => (new CleanupRegistryService())->providers(),
        ]);
    }

    public function cleanup()
    {
        if (! $this->request->is('post')) {
            throw PageNotFoundException::forPageNotFound();
        }
        $result = (new CleanupRegistryService())->runAll();
        (new AdminActionLoggerService())->log('system.cleanup');
        return redirect()->to('/admin/system/maintenance')->with('maintenance_result', $result)->with('success', 'Операции обслуживания выполнены.');
    }
}
