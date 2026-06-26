<?php

namespace Modules\Menu\Models;

use CodeIgniter\Model;

class MenuItemModel extends Model
{
    protected $table = 'menu_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'menu_key','item_key','parent_key','title','link_type','url','route_name','entity_type','entity_id','langcode','icon','module','permission','target','weight','is_active','is_system','created_at','updated_at','deleted_at'
    ];
}
