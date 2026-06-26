<?php

namespace Modules\Installer\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Installer\Services\InstallerService;
use Modules\Installer\Services\InstallerStateService;
use Throwable;

final class InstallerController extends Controller
{
    public function index(): string
    {
        $state = new InstallerStateService();
        if ($state->isInstalled()) {
            throw PageNotFoundException::forPageNotFound();
        }
        if (! $state->installerEnabled()) {
            return view('Modules\Installer\Views\disabled');
        }

        $token = bin2hex(random_bytes(32));
        session()->set('cms_installer_token', hash('sha256', $token));
        return view('Modules\Installer\Views\index', [
            'requirements' => $state->requirements(),
            'requirementsOk' => $state->requirementsOk(),
            'installerToken' => $token,
        ]);
    }

    public function install()
    {
        if (! $this->request->is('post')) {
            throw PageNotFoundException::forPageNotFound();
        }
        $expected = (string) session()->get('cms_installer_token');
        $provided = (string) $this->request->getPost('installer_token');
        if ($expected === '' || $provided === '' || ! hash_equals($expected, hash('sha256', $provided))) {
            return redirect()->to('/install')->with('error', 'Сессия установщика истекла. Повторите ввод данных.');
        }

        session()->remove('cms_installer_token');
        try {
            (new InstallerService())->install((array) $this->request->getPost());
            return redirect()->to('/login')->with('success', 'CMS установлена. Выполните вход под созданным администратором.');
        } catch (Throwable $e) {
            log_message('error', 'Installer failed: ' . $e->getMessage());
            return redirect()->to('/install')->with('error', $e->getMessage())->withInput();
        }
    }
}
