<?php

namespace Modules\Settings\Models;

use CodeIgniter\Model;

class SystemSettingModel extends Model
{
    protected $table = 'system_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'setting_group', 'setting_key', 'setting_label', 'setting_value',
        'field_type', 'field_options', 'description', 'is_public', 'is_system',
        'sort_order', 'is_required', 'min_value', 'max_value', 'validation_rule', 'is_secret', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = false;

    public function findByKey(string $key): ?array
    {
        return $this->where('setting_key', $key)->first();
    }
}
