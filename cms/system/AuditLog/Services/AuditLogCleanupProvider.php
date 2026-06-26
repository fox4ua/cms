<?php

namespace Modules\AuditLog\Services;

use Modules\Kernel\Contracts\MaintenanceProviderInterface;

final class AuditLogCleanupProvider implements MaintenanceProviderInterface
{
    public function key(): string { return 'audit_log.cleanup'; }
    public function label(): string { return 'Audit Log: журналы старше срока хранения'; }

    public function run(): array
    {
        $days = 365;
        if (class_exists('Modules\\Settings\\Services\\SettingService')) {
            $days = (int) (new \Modules\Settings\Services\SettingService())->get('audit_log_retention_days', 365);
        }
        if ($days <= 0) {
            return ['retention_disabled' => true];
        }
        $db = db_connect();
        $before = date('Y-m-d H:i:s', time() - $days * 86400);
        $result = [];
        foreach (['admin_action_logs', 'suspicious_logs'] as $table) {
            if ($db->tableExists($table)) {
                $db->table($table)->where('created_at <', $before)->delete();
                $result[$table] = $db->affectedRows();
            }
        }
        return $result;
    }
}
