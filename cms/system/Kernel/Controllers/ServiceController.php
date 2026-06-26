<?php

namespace Modules\Kernel\Controllers;

use CodeIgniter\Controller;

class ServiceController extends Controller
{
    public function index(string $code = 'SERVICE'): string
    {
        $reason = match ($code) {
            'DB-001' => 'Нет соединения с базой данных.',
            'MAINT-001' => 'CMS находится в режиме обслуживания.',
            'INSTALL' => 'Требуется установка CMS.',
            'UPDATE' => 'Требуется обновление CMS.',
            default => 'Сервис временно недоступен.',
        };

        return view('Modules\Kernel\Views\service\service', [
            'code' => $code,
            'reason' => $reason,
        ]);
    }

    public function install(): string
    {
        return $this->index('INSTALL');
    }

    public function updateRequired(): string
    {
        return $this->index('UPDATE');
    }
}
