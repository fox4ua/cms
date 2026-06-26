<?php

namespace Modules\ModuleManager\Models;

use CodeIgniter\Model;

final class ModuleOperationLogModel extends Model
{
    protected $table = 'module_operation_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'operation_id', 'module', 'operation', 'requested_by', 'owner_ip',
        'from_version', 'to_version', 'status', 'message', 'error_class',
        'error_hash', 'context_json', 'started_at', 'finished_at', 'duration_ms',
    ];
}
