<?php

namespace Modules\Auth\Models;

use CodeIgniter\Model;

class AuthSettingModel extends Model
{
    protected $table = 'auth_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['setting_key', 'setting_value', 'updated_at'];
    protected $useTimestamps = false;
}
