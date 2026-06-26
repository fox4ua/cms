<?php
namespace Modules\Auth\Models;
use CodeIgniter\Model;
class AuthExtensionHookModel extends Model
{
    protected $table = 'auth_extension_hooks';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['module','hook_name','handler_class','handler_method','priority','is_active','created_at','updated_at'];
    protected $useTimestamps = false;
}
