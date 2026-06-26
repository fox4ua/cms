<?php

namespace Modules\Settings\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\Kernel\Services\AdminActionLoggerService;
use Modules\Kernel\Services\CmsCacheService;
use Modules\Settings\Models\SystemSettingModel;
use Modules\Settings\Services\SettingService;
use Modules\Settings\Services\SettingValidationService;
use Throwable;

class SettingsController extends AdminController
{
    private array $groupLabels = [
        'site' => 'Сайт',
        'localization' => 'Локализация',
        'mail' => 'Почта',
        'files' => 'Файлы',
        'logging' => 'Логирование',
        'system' => 'Система',
        'security' => 'HTTP-безопасность',
    ];

    public function index(string $group = 'site')
    {
        $service = new SettingService();
        $groups = $service->groups();
        if (! in_array($group, $groups, true)) {
            $group = $groups[0] ?? 'site';
        }

        return $this->render('Modules\Settings\Views\index', [
            'pageTitle' => 'Настройки CMS',
            'activeGroup' => $group,
            'groupLabels' => $this->groupLabels,
            'groups' => $groups,
            'settings' => $service->group($group),
        ]);
    }

    public function save(string $group = 'site')
    {
        $model = new SystemSettingModel();
        $service = new SettingService();
        $validator = new SettingValidationService();
        $settings = $model->where('setting_group', $group)->findAll();
        $post = $this->request->getPost();

        try {
            foreach ($settings as $item) {
                $key = $item['setting_key'];
                $type = $item['field_type'];
                $value = $type === 'checkbox' ? (isset($post[$key]) ? '1' : '0') : (string) ($post[$key] ?? '');
                $value = $validator->validate($item, $value);
                $service->set($key, $value);
            }
        } catch (Throwable $e) {
            return redirect()->to('/admin/settings/' . $group)->with('error', $e->getMessage());
        }

        (new CmsCacheService())->clearSettings();
        (new AdminActionLoggerService())->log('settings.update', 'settings', $group);
        return redirect()->to('/admin/settings/' . $group)->with('success', 'Настройки сохранены');
    }
}
