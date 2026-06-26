<?php

namespace Modules\Maintenance\Services;

use Modules\Kernel\Contracts\MaintenanceProviderInterface;
use Modules\Kernel\Services\ModulePathService;
use Throwable;

final class CleanupRegistryService
{
    /** @return MaintenanceProviderInterface[] */
    public function providers(): array
    {
        $providers = [];
        foreach ((new ModulePathService())->manifests() as $manifestInfo) {
            $meta = include $manifestInfo['file'];
            if (! is_array($meta)) {
                continue;
            }
            $machine = (string) ($meta['machine_name'] ?? '');
            if ($machine !== '' && ! $this->isEnabled($machine)) {
                continue;
            }
            foreach ((array) ($meta['maintenance_providers'] ?? []) as $class) {
                if (! is_string($class) || ! class_exists($class)) {
                    continue;
                }
                $provider = new $class();
                if ($provider instanceof MaintenanceProviderInterface) {
                    $providers[$provider->key()] = $provider;
                }
            }
        }
        ksort($providers);
        return array_values($providers);
    }

    public function runAll(): array
    {
        $result = [];
        foreach ($this->providers() as $provider) {
            try {
                $result[$provider->key()] = ['ok' => true, 'label' => $provider->label(), 'result' => $provider->run()];
            } catch (Throwable $e) {
                $result[$provider->key()] = ['ok' => false, 'label' => $provider->label(), 'error' => $e->getMessage()];
            }
        }
        return $result;
    }

    private function isEnabled(string $machine): bool
    {
        try {
            $db = db_connect();
            if (! $db->tableExists('cms_modules')) {
                return true;
            }
            $row = $db->table('cms_modules')->select('is_enabled,is_installed')->where('machine_name', $machine)->get()->getRowArray();
            return ! $row || ((int) $row['is_installed'] === 1 && (int) $row['is_enabled'] === 1);
        } catch (Throwable) {
            return true;
        }
    }
}
