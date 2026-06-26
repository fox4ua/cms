<?php

namespace Modules\Auth\Models;

use CodeIgniter\Model;

class IpRuleModel extends Model
{
    protected $table = 'auth_ip_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['ip_value', 'rule_type', 'description', 'is_active', 'created_at', 'updated_at'];
    protected $useTimestamps = false;
}
