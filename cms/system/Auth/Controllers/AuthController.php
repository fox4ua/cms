<?php

namespace Modules\Auth\Controllers;

use CodeIgniter\Controller;
use Modules\Auth\Services\AuthService;

class AuthController extends Controller
{
    public function login()
    {
        $auth = new AuthService();
        if ($auth->autoLoginFromRemember()) {
            return redirect()->to('/admin');
        }
        return view('Modules\Auth\Views\login', ['title' => 'Login']);
    }

    public function attemptLogin()
    {
        if (! $this->validate(['email' => 'required|valid_email', 'password' => 'required|min_length[8]'])) {
            return redirect()->back()->withInput()->with('error', 'Проверьте email и пароль');
        }

        $result = (new AuthService())->login(
            (string) $this->request->getPost('email'),
            (string) $this->request->getPost('password'),
            (bool) $this->request->getPost('remember')
        );

        if (! $result['success']) {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }
        return redirect()->to('/admin');
    }

    public function logout()
    {
        (new \Modules\Auth\Services\AuthExtensionPipelineService())->run('before_logout', ['user_id' => session()->get('user_id')]);
        (new AuthService())->logout(true);
        return redirect()->to('/login');
    }
}
