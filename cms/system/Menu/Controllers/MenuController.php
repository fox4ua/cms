<?php

namespace Modules\Menu\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\Menu\Models\MenuModel;
use Modules\Menu\Models\MenuItemModel;
use Modules\Menu\Services\MenuService;
use Modules\Kernel\Services\AdminActionLoggerService;
use Modules\Kernel\Services\CmsCacheService;

class MenuController extends AdminController
{
    public function index(?string $menuKey = null)
    {
        $menus = (new MenuModel())->orderBy('area','ASC')->orderBy('title','ASC')->findAll();
        $menuKey = $menuKey ?: (string) ($this->request->getGet('menu') ?: 'admin_sidebar');
        $items = (new MenuItemModel())->where('menu_key', $menuKey)->orderBy('weight','ASC')->orderBy('id','ASC')->findAll();

        return $this->render('Modules\Menu\Views\index', [
            'pageTitle' => 'Меню',
            'menus' => $menus,
            'currentMenuKey' => $menuKey,
            'items' => $items,
            'tree' => (new MenuService())->buildTree($items),
        ]);
    }

    public function createMenu()
    {
        return $this->render('Modules\Menu\Views\menus\form', [
            'pageTitle' => 'Создать меню',
            'menu' => null,
        ]);
    }

    public function editMenu($id)
    {
        $menu = (new MenuModel())->find((int) $id);
        if (! $menu) {
            return redirect()->to('/admin/menu')->with('error', 'Меню не найдено');
        }
        return $this->render('Modules\Menu\Views\menus\form', [
            'pageTitle' => 'Редактировать меню',
            'menu' => $menu,
        ]);
    }

    public function saveMenu($id = null)
    {
        if (! $this->request->is('post')) {
            return redirect()->to('/admin/menu');
        }

        $service = new MenuService();
        $model = new MenuModel();
        $menuKey = trim((string) $this->request->getPost('menu_key'));
        $title = trim((string) $this->request->getPost('title'));
        $area = trim((string) $this->request->getPost('area')) ?: 'frontend';

        if (! $service->validateMenuKey($menuKey)) {
            return redirect()->back()->withInput()->with('error', 'Некорректный ключ меню. Используйте латиницу, цифры и подчёркивание.');
        }
        if ($title === '') {
            return redirect()->back()->withInput()->with('error', 'Название меню обязательно.');
        }

        $exists = $model->where('menu_key', $menuKey)->first();
        if ($exists && (!$id || (int) $exists['id'] !== (int) $id)) {
            return redirect()->back()->withInput()->with('error', 'Меню с таким ключом уже существует.');
        }

        $data = [
            'menu_key' => $menuKey,
            'title' => $title,
            'description' => trim((string) $this->request->getPost('description')),
            'area' => preg_replace('~[^a-z0-9_:-]~i', '', $area),
            'module' => preg_replace('~[^A-Za-z0-9_]~', '', trim((string) $this->request->getPost('module'))),
            'is_active' => (int) ($this->request->getPost('is_active') ?? 1),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            $menu = $model->find((int) $id);
            if ($menu && (int) $menu['is_system'] === 1 && $menu['menu_key'] !== $menuKey) {
                return redirect()->back()->with('error', 'Нельзя менять ключ системного меню.');
            }
            $model->update((int) $id, $data);
            (new AdminActionLoggerService())->log('menu.update', 'menu', $menuKey);
        } else {
            $data['is_system'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $model->insert($data);
            (new AdminActionLoggerService())->log('menu.create', 'menu', $menuKey);
        }

        (new CmsCacheService())->clearMenu();
        return redirect()->to('/admin/menu?menu=' . rawurlencode($menuKey))->with('success', 'Меню сохранено');
    }

    public function createItem(?string $menuKey = null)
    {
        $menuKey = $menuKey ?: (string) ($this->request->getGet('menu') ?: 'admin_sidebar');
        return $this->itemForm(null, $menuKey);
    }

    public function editItem($id)
    {
        $item = (new MenuItemModel())->find((int) $id);
        if (! $item) {
            return redirect()->to('/admin/menu')->with('error', 'Пункт меню не найден');
        }
        return $this->itemForm($item, $item['menu_key']);
    }

    private function itemForm(?array $item, string $menuKey)
    {
        $menus = (new MenuModel())->orderBy('title','ASC')->findAll();
        $parents = (new MenuItemModel())->where('menu_key', $menuKey)->whereIn('link_type', ['heading','separator'])->orderBy('weight','ASC')->findAll();
        return $this->render('Modules\Menu\Views\items\form', [
            'pageTitle' => $item ? 'Редактировать пункт меню' : 'Создать пункт меню',
            'item' => $item,
            'menus' => $menus,
            'parents' => $parents,
            'currentMenuKey' => $menuKey,
        ]);
    }

    public function saveItem($id = null)
    {
        if (! $this->request->is('post')) {
            return redirect()->to('/admin/menu');
        }
        $service = new MenuService();
        $model = new MenuItemModel();
        $menuKey = trim((string) $this->request->getPost('menu_key'));
        $itemKey = trim((string) $this->request->getPost('item_key'));
        $parentKey = trim((string) $this->request->getPost('parent_key'));
        $title = trim((string) $this->request->getPost('title'));
        $linkType = trim((string) $this->request->getPost('link_type')) ?: 'url';
        $url = trim((string) $this->request->getPost('url')) ?: '#';
        $routeName = trim((string) $this->request->getPost('route_name'));
        $entityType = trim((string) $this->request->getPost('entity_type'));
        $entityId = trim((string) $this->request->getPost('entity_id'));
        $langcode = trim((string) $this->request->getPost('langcode'));

        if (! $service->validateMenuKey($menuKey) || ! $service->validateItemKey($itemKey)) {
            return redirect()->back()->withInput()->with('error', 'Некорректный ключ меню или пункта.');
        }
        if ($parentKey !== '' && ! $service->validateItemKey($parentKey)) {
            return redirect()->back()->withInput()->with('error', 'Некорректный ключ родителя.');
        }
        if ($parentKey !== '' && $parentKey === $itemKey) {
            return redirect()->back()->withInput()->with('error', 'Пункт не может быть родителем самого себя.');
        }
        if ($title === '' || ! $service->validator()->validateLinkType($linkType) || ! $service->validateUrl($url) || ! $service->validator()->validateTarget(trim((string) $this->request->getPost('target'))) || ! $service->validator()->validateLangcode($langcode)) {
            return redirect()->back()->withInput()->with('error', 'Название, тип ссылки, URL, target или язык некорректны.');
        }

        $allItems = $model->where('menu_key', $menuKey)->findAll();
        if ($service->validator()->wouldCreateCycle($allItems, $itemKey, $parentKey)) {
            return redirect()->back()->withInput()->with('error', 'Такая вложенность создаёт цикл меню.');
        }
        if ($service->validator()->exceedsMaxDepth($allItems, $itemKey, $parentKey, 4)) {
            return redirect()->back()->withInput()->with('error', 'Превышена максимальная глубина меню: 4 уровня.');
        }

        $exists = $model->where('menu_key', $menuKey)->where('item_key', $itemKey)->first();
        if ($exists && (!$id || (int) $exists['id'] !== (int) $id)) {
            return redirect()->back()->withInput()->with('error', 'В этом меню уже есть пункт с таким ключом.');
        }

        if ($parentKey !== '') {
            $parent = $model->where('menu_key', $menuKey)->where('item_key', $parentKey)->first();
            if (! $parent) {
                return redirect()->back()->withInput()->with('error', 'Родительский пункт не найден.');
            }
        }

        $data = [
            'menu_key' => $menuKey,
            'item_key' => $itemKey,
            'parent_key' => $parentKey,
            'title' => $title,
            'link_type' => $linkType,
            'url' => $url,
            'route_name' => $routeName,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'langcode' => $langcode,
            'icon' => trim((string) $this->request->getPost('icon')),
            'module' => preg_replace('~[^A-Za-z0-9_]~', '', trim((string) $this->request->getPost('module'))),
            'permission' => trim((string) $this->request->getPost('permission')),
            'target' => trim((string) $this->request->getPost('target')),
            'weight' => max(-9999, min(9999, (int) $this->request->getPost('weight'))),
            'is_active' => (int) ($this->request->getPost('is_active') ?? 1),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            $item = $model->find((int) $id);
            if ($item && (int) $item['is_system'] === 1 && ($item['menu_key'] !== $menuKey || $item['item_key'] !== $itemKey)) {
                return redirect()->back()->with('error', 'Нельзя менять ключ системного пункта меню.');
            }
            $model->update((int) $id, $data);
            (new AdminActionLoggerService())->log('menu.item.update', 'menu_item', $itemKey);
        } else {
            $data['is_system'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $model->insert($data);
            (new AdminActionLoggerService())->log('menu.item.create', 'menu_item', $itemKey);
        }

        (new CmsCacheService())->clearMenu();
        return redirect()->to('/admin/menu?menu=' . rawurlencode($menuKey))->with('success', 'Пункт меню сохранён');
    }

    public function toggleItem($id)
    {
        $model = new MenuItemModel();
        $item = $model->find((int) $id);
        if ($item) {
            $model->update((int) $id, ['is_active' => (int) $item['is_active'] === 1 ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')]);
            (new CmsCacheService())->clearMenu();
        }
        return redirect()->back();
    }

    public function deleteItem($id)
    {
        $model = new MenuItemModel();
        $item = $model->find((int) $id);
        if (! $item) {
            return redirect()->back()->with('error', 'Пункт меню не найден');
        }
        if ((int) $item['is_system'] === 1) {
            return redirect()->back()->with('error', 'Системный пункт меню нельзя удалить');
        }
        $children = $model->where('menu_key', $item['menu_key'])->where('parent_key', $item['item_key'])->countAllResults();
        if ($children > 0) {
            return redirect()->back()->with('error', 'Нельзя удалить пункт с дочерними элементами');
        }
        $model->delete((int) $id);
        (new CmsCacheService())->clearMenu();
        (new AdminActionLoggerService())->log('menu.item.delete', 'menu_item', (string) $id);
        return redirect()->back()->with('success', 'Пункт меню удалён');
    }
}
