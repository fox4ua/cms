<?php

namespace Modules\ModuleManager\Services;

use Modules\ModuleManager\Models\CmsModuleModel;
use Modules\Kernel\Services\ModulePathService;

class ModuleRegistryService
{
    public function sync(): int
    {
        $paths = new ModulePathService();
        $model = new CmsModuleModel();
        $count = 0;

        foreach ($paths->manifests() as $manifest) {
            $file = $manifest['file'];
            $meta = include $file;
            if (! is_array($meta) || empty($meta['machine_name'])) {
                continue;
            }

            $machine = (string) $meta['machine_name'];
            $exists = $model->where('machine_name', $machine)->first();
            $version = (string) ($meta['version'] ?? '1.0.0');
            $data = [
                'machine_name' => $machine,
                'name' => (string) ($meta['name'] ?? $machine),
                'description' => (string) ($meta['description'] ?? ''),
                'version' => $version,
                'available_version' => $version,
                'is_system' => (int) ($meta['is_system'] ?? 0),
                'dependencies' => json_encode($meta['dependencies'] ?? [], JSON_UNESCAPED_UNICODE),
                'source_type' => (string) ($manifest['type'] ?? 'modules'),
                'source_path' => str_replace(ROOTPATH, '', (string) ($manifest['base'] ?? dirname($file))),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($exists) {
                if (empty($exists['installed_version'])) {
                    $data['installed_version'] = $exists['version'] ?? $version;
                }
                if (empty($exists['install_status'])) {
                    $data['install_status'] = 'installed';
                    $data['is_installed'] = 1;
                }
                $model->update($exists['id'], $data);
            } else {
                $data['installed_version'] = '0.0.0';
                $data['install_status'] = 'discovered';
                $data['is_installed'] = 0;
                $data['is_enabled'] = 0;
                $data['menu_order'] = 100;
                $data['installed_at'] = null;
                $model->insert($data);
            }
            $count++;
        }

        return $count;
    }

    public function meta(string $machineName): array
    {
        $file = (new ModulePathService())->manifest($machineName);
        if ($file === null || ! is_file($file)) {
            return [];
        }
        $meta = include $file;
        return is_array($meta) ? $meta : [];
    }

    public function enabled(string $machineName): bool
    {
        $row = (new CmsModuleModel())->where('machine_name', $machineName)->first();
        return $row ? (int) $row['is_installed'] === 1 && (int) $row['is_enabled'] === 1 : false;
    }
}
