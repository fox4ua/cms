<?php

namespace Modules\Auth\Models;

use CodeIgniter\Model;

class LoginFailedModel extends Model
{
    protected $table = 'user_login_failed';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['email','ip_address','attempts','blocked_until','last_attempt_at'];
}
