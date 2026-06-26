<?php

namespace Modules\SystemHealth\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\SystemHealth\Services\SystemHealthService;

final class SystemHealthController extends AdminController
{
    public function index()
    {
        $service = new SystemHealthService();
        return $this->render('Modules\SystemHealth\Views\index', [
            'pageTitle' => 'Состояние системы',
            'checks' => $service->checks(),
            'readiness' => $service->readiness(),
            'score' => $service->productionScore(),
        ]);
    }
}
