<?php

namespace Modules\Kernel\Services;

use Throwable;

final class AdminActionLoggerService
{
    public function log(string $action, ?string $entityType = null, ?string $entityId = null): void
    {
        try {
            db_connect()->table('admin_action_logs')->insert([
                'admin_id' => session()->get('user_id'),
                'action' => mb_substr($action, 0, 100),
                'entity_type' => $entityType !== null ? mb_substr($entityType, 0, 100) : null,
                'entity_id' => $entityId !== null ? mb_substr($entityId, 0, 100) : null,
                'ip_address' => (new ClientIpResolver())->ip(),
                'user_agent' => mb_substr((string) service('request')->getUserAgent(), 0, 255),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Audit log write failed: ' . $e->getMessage());
        }
    }

    public function suspicious(string $event, array $context = []): void
    {
        try {
            $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
            db_connect()->table('suspicious_logs')->insert([
                'user_id' => session()->get('user_id'),
                'event' => mb_substr($event, 0, 100),
                'context_json' => $json === false ? '{}' : $json,
                'ip_address' => (new ClientIpResolver())->ip(),
                'user_agent' => mb_substr((string) service('request')->getUserAgent(), 0, 255),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('critical', 'Suspicious event log write failed: ' . $e->getMessage());
        }
    }
}
