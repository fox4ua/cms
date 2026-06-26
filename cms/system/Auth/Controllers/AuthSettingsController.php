<?php

namespace Modules\Auth\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\Auth\Services\SecuritySettingsService;
use Modules\Kernel\Services\AdminActionLoggerService;

class AuthSettingsController extends AdminController
{
    public function index()
    {
        return $this->render('Modules\Auth\Views\settings', [
            'pageTitle' => 'Настройки авторизации',
            'settings' => (new SecuritySettingsService())->all(),
        ]);
    }

    public function save()
    {
        $post = $this->request->getPost();
        (new SecuritySettingsService())->save($post);
        (new AdminActionLoggerService())->log('auth.settings.update');
        return redirect()->to('/admin/auth/settings')->with('success', 'Настройки сохранены');
    }
}
