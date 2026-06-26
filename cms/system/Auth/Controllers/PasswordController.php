<?php

namespace Modules\Auth\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\Auth\Models\UserModel;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\PasswordPolicyService;
use Modules\Kernel\Services\AdminActionLoggerService;

class PasswordController extends AdminController
{
    public function change()
    {
        return $this->render('Modules\Auth\Views\change_password', ['pageTitle' => 'Смена пароля']);
    }

    public function save()
    {
        $userId = (string) session()->get('user_id');
        $currentPassword = (string) $this->request->getPost('current_password');
        $password = (string) $this->request->getPost('password');
        $confirm = (string) $this->request->getPost('password_confirm');
        $user = (new UserModel())->find($userId);
        if (! $user || ! password_verify($currentPassword, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Текущий пароль указан неверно');
        }
        if ($password !== $confirm) return redirect()->back()->with('error', 'Пароли не совпадают');

        $policy = new PasswordPolicyService();
        $result = $policy->validate($password, $userId);
        if (! $result['valid']) return redirect()->back()->with('error', implode('<br>', $result['errors']));

        $hash = (new AuthService())->hashPassword($password);
        (new UserModel())->update($userId, ['password_hash' => $hash, 'password_changed_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        $policy->rememberPassword($userId, $hash);
        (new AdminActionLoggerService())->log('auth.password.change', 'user', $userId);
        return redirect()->to('/admin')->with('success', 'Пароль изменён');
    }
}
