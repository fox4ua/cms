<?php

namespace Modules\Auth\Services;

use Modules\Kernel\Contracts\MaintenanceProviderInterface;

final class AuthCleanupProvider implements MaintenanceProviderInterface
{
    public function key(): string { return 'auth.cleanup'; }
    public function label(): string { return 'Auth: токены, сессии и старые попытки входа'; }

    public function run(): array
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $result = [];
        if ($db->tableExists('user_tokens')) {
            $db->table('user_tokens')->where('expires_at <', $now)->delete();
            $result['expired_tokens'] = $db->affectedRows();
        }
        if ($db->tableExists('user_login_failed')) {
            $db->table('user_login_failed')->where('last_attempt_at <', date('Y-m-d H:i:s', time() - 86400 * 30))->delete();
            $result['old_failed_logins'] = $db->affectedRows();
        }
        if ($db->tableExists('active_sessions')) {
            $db->table('active_sessions')
                ->groupStart()->where('expires_at <', $now)->orWhere('revoked_at IS NOT NULL', null, false)->groupEnd()
                ->where('last_activity_at <', date('Y-m-d H:i:s', time() - 86400 * 30))
                ->delete();
            $result['old_sessions'] = $db->affectedRows();
        }
        return $result;
    }
}
