<?php

namespace Modules\Menu\Models;

use CodeIgniter\Model;

class MenuModel extends Model
{
    protected $table = 'menus';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'menu_key','title','description','area','module','is_system','is_active','created_at','updated_at'
    ];
}
