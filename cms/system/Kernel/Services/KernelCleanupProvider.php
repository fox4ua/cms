<?php

namespace Modules\Kernel\Services;

use Modules\Kernel\Contracts\MaintenanceProviderInterface;

final class KernelCleanupProvider implements MaintenanceProviderInterface
{
    public function key(): string { return 'kernel.cache'; }
    public function label(): string { return 'Kernel: временный rate-limit cache'; }

    public function run(): array
    {
        $removed = 0;
        $directory = WRITEPATH . 'cache/rate-limit';
        foreach (glob($directory . '/*.json') ?: [] as $file) {
            if (@filemtime($file) !== false && @filemtime($file) < time() - 3600 && @unlink($file)) {
                $removed++;
            }
        }
        return ['removed_rate_limit_files' => $removed];
    }
}
