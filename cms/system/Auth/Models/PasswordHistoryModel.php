<?php

namespace Modules\Auth\Models;

use CodeIgniter\Model;

class PasswordHistoryModel extends Model
{
    protected $table = 'user_password_history';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'password_hash', 'created_at'];
    protected $useTimestamps = false;
}
