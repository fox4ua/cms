<?php

namespace Modules\ModuleManager\Services;

use Modules\Kernel\Services\ClientIpResolver;
use Modules\ModuleManager\Models\ModuleOperationLogModel;
use Throwable;

final class ModuleOperationLoggerService
{
    public function start(string $module, string $operation, ?string $from = null, ?string $to = null, array $context = []): array
    {
        $operationId = bin2hex(random_bytes(16));
        $startedAt = microtime(true);
        try {
            $db = db_connect();
            if (! $db->tableExists('module_operation_logs')) {
                return ['id' => 0, 'operation_id' => $operationId, 'started' => $startedAt];
            }
            $id = (new ModuleOperationLogModel())->insert([
                'operation_id' => $operationId,
                'module' => $module,
                'operation' => $operation,
                'requested_by' => session()->get('user_id') ?: null,
                'owner_ip' => (new ClientIpResolver())->ip(),
                'from_version' => $from,
                'to_version' => $to,
                'status' => 'running',
                'context_json' => $context === [] ? null : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'started_at' => date('Y-m-d H:i:s'),
            ], true);
            return ['id' => (int) $id, 'operation_id' => $operationId, 'started' => $startedAt];
        } catch (Throwable) {
            return ['id' => 0, 'operation_id' => $operationId, 'started' => $startedAt];
        }
    }

    public function success(array $operation, string $message = ''): void
    {
        $this->finish($operation, 'success', $message, null);
    }

    public function failure(array $operation, Throwable $error): void
    {
        $this->finish($operation, 'error', $error->getMessage(), $error);
    }

    private function finish(array $operation, string $status, string $message, ?Throwable $error): void
    {
        if ((int) ($operation['id'] ?? 0) < 1) {
            return;
        }
        try {
            (new ModuleOperationLogModel())->update((int) $operation['id'], [
                'status' => $status,
                'message' => mb_substr($message, 0, 2000),
                'error_class' => $error ? get_class($error) : null,
                'error_hash' => $error ? hash('sha256', get_class($error) . '|' . $error->getMessage() . '|' . $error->getFile() . ':' . $error->getLine()) : null,
                'finished_at' => date('Y-m-d H:i:s'),
                'duration_ms' => (int) round((microtime(true) - (float) $operation['started']) * 1000),
            ]);
        } catch (Throwable) {
        }
    }
}
