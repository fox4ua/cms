<?php
namespace Modules\Auth\Services;

use Modules\Auth\Models\AuthExtensionHookModel;
use Modules\Kernel\Services\ModulePathService;
use Modules\Kernel\Services\CmsCacheService;
use Modules\ModuleManager\Models\CmsModuleModel;

class AuthExtensionRegistryService
{
    public function syncAllInstalled(): int
    {
        $count = 0;
        foreach ((new CmsModuleModel())->where('is_installed', 1)->findAll() as $module) {
            $file = (new ModulePathService())->manifest((string) $module['machine_name']);
            if ($file === null || ! is_file($file)) continue;
            $meta = include $file;
            if (is_array($meta)) $count += $this->syncModuleHooks($module['machine_name'], $meta, (int)$module['is_enabled'] === 1);
        }
        (new CmsCacheService())->clearKernel();
        return $count;
    }

    public function syncModuleHooks(string $module, array $meta, bool $active = true): int
    {
        $model = new AuthExtensionHookModel();
        $count = 0;
        foreach ((array)($meta['auth_hooks'] ?? []) as $hook) {
            if (! is_array($hook)) continue;
            $hookName = (string)($hook['hook'] ?? $hook['hook_name'] ?? '');
            $class = (string)($hook['class'] ?? $hook['handler_class'] ?? '');
            $method = (string)($hook['method'] ?? $hook['handler_method'] ?? 'handle');
            if ($hookName === '' || $class === '' || ! AuthHookNames::isAllowed($hookName)) continue;
            $data = [
                'module' => $module,
                'hook_name' => $hookName,
                'handler_class' => $class,
                'handler_method' => $method,
                'priority' => (int)($hook['priority'] ?? 100),
                'is_active' => $active ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $exists = $model->where('module',$module)->where('hook_name',$hookName)->where('handler_class',$class)->where('handler_method',$method)->first();
            if ($exists) $model->update($exists['id'], $data); else { $data['created_at'] = date('Y-m-d H:i:s'); $model->insert($data); }
            $count++;
        }
        return $count;
    }

    public function setModuleHooksActive(string $module, bool $active): void
    {
        (new AuthExtensionHookModel())->where('module', $module)->set(['is_active' => $active ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')])->update();
        (new CmsCacheService())->clearKernel();
    }
}
