<?php

namespace Modules\Auth\Models;

use CodeIgniter\Model;

class ActiveSessionModel extends Model
{
    protected $table = 'active_sessions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['id','user_id','session_hash','ip_address','user_agent_hash','created_at','last_activity_at','expires_at','revoked_at'];
}
