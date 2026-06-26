<?php

namespace Modules\ModuleManager\Models;

use CodeIgniter\Model;

class CmsModuleModel extends Model
{
    protected $table = 'cms_modules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'machine_name','name','description','version','available_version','installed_version',
        'source_type','source_path',
        'install_status','is_installed','is_enabled','is_system','menu_order','dependencies',
        'last_error','installed_at','updated_at'
    ];
}
