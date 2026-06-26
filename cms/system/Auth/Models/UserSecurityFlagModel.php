<?php

namespace Modules\Auth\Models;

use CodeIgniter\Model;

class UserSecurityFlagModel extends Model
{
    protected $table = 'user_security_flags';
    protected $primaryKey = 'user_id';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'force_password_change', 'password_changed_at', 'two_factor_enabled', 'two_factor_secret', 'login_allowed_from', 'login_allowed_until', 'allowed_ip_list', 'denied_ip_list', 'updated_at'];
    protected $useTimestamps = false;
}
