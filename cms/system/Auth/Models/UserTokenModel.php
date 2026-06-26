<?php

namespace Modules\Auth\Models;

use CodeIgniter\Model;

class UserTokenModel extends Model
{
    protected $table = 'user_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id','selector','token_hash','type','ip_address','user_agent_hash','expires_at','last_used_at','revoked_at','created_at'];
}
