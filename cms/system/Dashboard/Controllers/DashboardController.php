<?php

namespace Modules\Dashboard\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\Kernel\Services\AdminActionLoggerService;

class DashboardController extends AdminController
{
    public function index()
    {
        (new AdminActionLoggerService())->log('dashboard.view');
        return $this->render('Modules\Dashboard\Views\dashboard', ['pageTitle' => 'Панель управления']);
    }
}
