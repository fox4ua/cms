<?php

namespace Modules\Kernel\Controllers;

use CodeIgniter\Controller;
use Modules\Kernel\Services\AdminGuardService;
use Modules\Kernel\Services\KernelMenuService;
use Modules\Kernel\Services\ModuleAccessService;

class AdminController extends Controller
{
    protected array $data = [];

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $helper = ROOTPATH . 'cms/system/Kernel/Helpers/admin_ui_helper.php';
        if (is_file($helper)) {
            require_once $helper;
        }

        (new ModuleAccessService())->assertCurrentModuleEnabled();

        $guard = new AdminGuardService();
        $guard->assertAuthenticated();
        $guard->enforcePostLoginRequirements();

        $this->data = [
            'currentUser' => session()->get('user'),
            'adminMenu' => (new KernelMenuService())->getMenu(),
            'pageTitle' => 'CMS',
        ];
    }

    protected function render(string $view, array $data = [])
    {
        $payload = array_merge($this->data, $data);
        $payload['content'] = view($view, $payload);
        return view('Modules\Kernel\Views\layout', $payload);
    }
}
