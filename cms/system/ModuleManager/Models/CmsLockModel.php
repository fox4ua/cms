<?php

namespace Modules\ModuleManager\Models;

use CodeIgniter\Model;

final class CmsLockModel extends Model
{
    protected $table = 'cms_locks';
    protected $primaryKey = 'lock_key';
    protected $returnType = 'array';
    protected $allowedFields = ['lock_key', 'lock_token', 'owner', 'operation', 'expires_at', 'created_at', 'updated_at'];
    protected $useAutoIncrement = false;
}
