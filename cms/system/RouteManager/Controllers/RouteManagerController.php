<?php
namespace Modules\RouteManager\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\Kernel\Services\AdminActionLoggerService;
use Modules\Kernel\Services\CmsCacheService;
use Modules\RouteManager\Models\CmsRouteModel;
use Modules\RouteManager\Services\RouteRegistryService;
use Modules\RouteManager\Services\RouteValidationService;
use Throwable;

class RouteManagerController extends AdminController
{
    public function index()
    {
        return $this->render('Modules\RouteManager\Views\index', [
            'pageTitle' => 'Маршруты CMS',
            'routesList' => (new CmsRouteModel())->orderBy('module','ASC')->orderBy('sort_order','ASC')->findAll(),
        ]);
    }

    public function create()
    {
        return $this->render('Modules\RouteManager\Views\form', ['pageTitle' => 'Создать маршрут', 'route' => null, 'errorsList' => []]);
    }

    public function edit($id)
    {
        $row = (new CmsRouteModel())->find((int)$id);
        if (! $row) return redirect()->to('/admin/routes')->with('error','Маршрут не найден');
        return $this->render('Modules\RouteManager\Views\form', ['pageTitle' => 'Редактировать маршрут', 'route' => $row, 'errorsList' => []]);
    }

    public function save($id = null)
    {
        $model = new CmsRouteModel();
        $validator = new RouteValidationService();
        $id = $id ? (int)$id : null;
        $old = $id ? $model->find($id) : null;
        if ($id && ! $old) return redirect()->to('/admin/routes')->with('error','Маршрут не найден');
        if ($old && (int)$old['is_system'] === 1 && (string)$old['path'] === '/admin/routes') {
            return redirect()->to('/admin/routes')->with('error','Главный маршрут RouteManager нельзя изменить');
        }
        $data = $validator->normalize($this->request->getPost());
        $errors = $validator->validate($data, $id);
        if ($errors) {
            $data['id'] = $id;
            return $this->render('Modules\RouteManager\Views\form', ['pageTitle' => $id ? 'Редактировать маршрут' : 'Создать маршрут', 'route' => $data, 'errorsList' => $errors]);
        }
        if ($id) $model->update($id, $data); else { $data['created_at'] = date('Y-m-d H:i:s'); $model->insert($data); }
        (new CmsCacheService())->clearKernel();
        (new AdminActionLoggerService())->log($id ? 'routes.update' : 'routes.create', 'route', (string)($id ?? $model->getInsertID()));
        return redirect()->to('/admin/routes')->with('success','Маршрут сохранён');
    }

    public function sync()
    {
        try {
            $count = (new RouteRegistryService())->syncAllInstalled();
            (new AdminActionLoggerService())->log('routes.sync');
            return redirect()->to('/admin/routes')->with('success', 'Синхронизировано маршрутов: ' . $count);
        } catch (Throwable $e) { return redirect()->to('/admin/routes')->with('error', $e->getMessage()); }
    }

    public function toggle($id)
    {
        $model = new CmsRouteModel(); $row = $model->find((int)$id);
        if (! $row) return redirect()->to('/admin/routes')->with('error','Маршрут не найден');
        if ((int)$row['is_system'] === 1) return redirect()->to('/admin/routes')->with('error','Системный маршрут нельзя отключить вручную');
        $model->update($row['id'], ['is_active' => (int)$row['is_active'] ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')]);
        (new CmsCacheService())->clearKernel();
        (new AdminActionLoggerService())->log('routes.toggle','route',(string)$row['id']);
        return redirect()->to('/admin/routes')->with('success','Статус маршрута изменён');
    }

    public function delete($id)
    {
        $model = new CmsRouteModel(); $row = $model->find((int)$id);
        if (! $row) return redirect()->to('/admin/routes')->with('error','Маршрут не найден');
        if ((int)$row['is_system'] === 1) return redirect()->to('/admin/routes')->with('error','Системный маршрут нельзя удалить');
        $model->delete($row['id']);
        (new CmsCacheService())->clearKernel();
        (new AdminActionLoggerService())->log('routes.delete','route',(string)$row['id']);
        return redirect()->to('/admin/routes')->with('success','Маршрут удалён');
    }
}
