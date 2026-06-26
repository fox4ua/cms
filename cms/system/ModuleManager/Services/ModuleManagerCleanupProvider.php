<?php

namespace Modules\ModuleManager\Services;

use Modules\Kernel\Contracts\MaintenanceProviderInterface;

final class ModuleManagerCleanupProvider implements MaintenanceProviderInterface
{
    public function key(): string { return 'module_manager.cleanup'; }
    public function label(): string { return 'Module Manager: просроченные locks и старые журналы'; }

    public function run(): array
    {
        $db = db_connect();
        $result = [];
        if ($db->tableExists('cms_locks')) {
            $db->table('cms_locks')->where('expires_at <', date('Y-m-d H:i:s'))->delete();
            $result['expired_locks'] = $db->affectedRows();
        }
        if ($db->tableExists('module_operation_logs')) {
            $db->table('module_operation_logs')
                ->where('started_at <', date('Y-m-d H:i:s', time() - 86400 * 365))
                ->whereIn('status', ['success', 'error'])
                ->delete();
            $result['old_operation_logs'] = $db->affectedRows();
        }
        return $result;
    }
}
