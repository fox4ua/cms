<?php

namespace Modules\Auth\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\Auth\Models\ActiveSessionModel;
use Modules\Kernel\Services\AdminActionLoggerService;

class SessionsController extends AdminController
{
    public function index()
    {
        $userId = (string) session()->get('user_id');
        $sessions = (new ActiveSessionModel())->where('user_id', $userId)->orderBy('last_activity_at', 'DESC')->findAll();
        return $this->render('Modules\Auth\Views\sessions', ['pageTitle' => 'Активные сессии', 'sessions' => $sessions, 'currentSessionId' => session()->get('auth_session_id')]);
    }

    public function revoke(string $id)
    {
        $userId = (string) session()->get('user_id');
        (new ActiveSessionModel())->where('id', $id)->where('user_id', $userId)->set(['revoked_at' => date('Y-m-d H:i:s')])->update();
        (new AdminActionLoggerService())->log('auth.session.revoke', 'active_session', $id);
        return redirect()->to('/admin/auth/sessions')->with('success', 'Сессия завершена');
    }

    public function revokeOthers()
    {
        $userId = (string) session()->get('user_id');
        $current = (string) session()->get('auth_session_id');
        (new ActiveSessionModel())->where('user_id', $userId)->where('id !=', $current)->where('revoked_at', null)->set(['revoked_at' => date('Y-m-d H:i:s')])->update();
        (new AdminActionLoggerService())->log('auth.session.revoke_others', 'user', $userId);
        return redirect()->to('/admin/auth/sessions')->with('success', 'Другие сессии завершены');
    }
}
