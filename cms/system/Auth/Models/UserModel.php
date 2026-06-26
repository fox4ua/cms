<?php

namespace Modules\Auth\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['id','email','password_hash','status','password_changed_at','last_login_at','created_at','updated_at'];
}
