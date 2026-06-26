<?php

namespace Modules\ModuleManager\Models;

use CodeIgniter\Model;

class CmsModuleUpdateModel extends Model
{
    protected $table = 'cms_module_updates';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'module','from_version','to_version','sql_file','status','error','executed_at','created_at'
    ];
    protected $useTimestamps = false;
}
