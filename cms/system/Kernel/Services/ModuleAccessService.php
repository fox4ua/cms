<?php

namespace Modules\Kernel\Services;

use Modules\ModuleManager\Models\CmsModuleModel;

class ModuleAccessService
{
    private array $systemModules = ['Kernel','Auth','ModuleManager','RouteManager','SystemHealth','Menu','Settings','Dashboard','AuditLog','Maintenance'];


    public function assertCurrentModuleEnabled(): void
    {
        $controller = service('router')->controllerName();
        $this->assertCurrentControllerEnabled((string) $controller);
    }

    public function assertCurrentControllerEnabled(string $controller): void
    {
        $module = $this->moduleFromController($controller);
        if ($module === null || in_array($module, $this->systemModules, true)) {
            return;
        }
        $row = (new CmsModuleModel())->where('machine_name', $module)->first();
        if (! $row || (int)($row['is_installed'] ?? 0) !== 1 || (int)($row['is_enabled'] ?? 0) !== 1) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    public function moduleFromController(string $controller): ?string
    {
        if (preg_match('~^\\\\?Modules\\\\([^\\\\]+)\\\\Controllers\\\\~', $controller, $m)) {
            return $m[1];
        }
        return null;
    }

    public function isSystemModule(string $machineName): bool
    {
        return in_array($machineName, $this->systemModules, true);
    }
}
