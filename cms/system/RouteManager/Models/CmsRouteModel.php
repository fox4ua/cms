<?php
namespace Modules\RouteManager\Models;
use CodeIgniter\Model;
class CmsRouteModel extends Model
{
    protected $table = 'cms_routes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['module','route_key','http_method','path','controller','action','is_admin','is_active','is_system','sort_order','created_at','updated_at'];
    protected $useTimestamps = false;
}
