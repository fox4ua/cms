<?php
namespace Modules\Auth\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\Auth\Models\AuthExtensionHookModel;
use Modules\Kernel\Services\AdminActionLoggerService;
use Modules\Kernel\Services\CmsCacheService;
use Modules\Auth\Services\AuthExtensionRegistryService;

class AuthExtensionsController extends AdminController
{
    public function index()
    {
        return $this->render('Modules\Auth\Views\extensions', [
            'pageTitle' => 'Расширения авторизации',
            'hooks' => (new AuthExtensionHookModel())->orderBy('hook_name','ASC')->orderBy('priority','ASC')->findAll(),
        ]);
    }
    public function sync()
    {
        $count = (new AuthExtensionRegistryService())->syncAllInstalled();
        (new AdminActionLoggerService())->log('auth_extension.sync');
        return redirect()->to('/admin/auth/extensions')->with('success', 'Синхронизировано hooks: ' . $count);
    }

    public function toggle($id)
    {
        $model = new AuthExtensionHookModel(); $row = $model->find((int)$id);
        if (! $row) return redirect()->to('/admin/auth/extensions')->with('error','Hook не найден');
        $model->update($row['id'], ['is_active' => (int)$row['is_active'] ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')]);
        (new CmsCacheService())->clearKernel();
        (new AdminActionLoggerService())->log('auth_extension.toggle','hook',(string)$row['id']);
        return redirect()->to('/admin/auth/extensions')->with('success','Статус hook изменён');
    }
}
