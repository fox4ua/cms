<?php

namespace Modules\Settings\Services;

use Modules\Settings\Models\SystemSettingModel;

class SettingService
{
    private string $cachePrefix = 'cms_setting_';
    private int $ttl = 3600;

    public function get(string $key, $default = null)
    {
        $cacheKey = $this->cachePrefix . $key;
        $cached = cache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            if (! db_connect()->tableExists('system_settings')) {
                return $default;
            }
            $row = (new SystemSettingModel())->findByKey($key);
            if (! $row) {
                return $default;
            }
            $value = $this->castValue($row['setting_value'], $row['field_type'] ?? 'text');
            cache()->save($cacheKey, $value, $this->ttl);
            return $value;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function set(string $key, $value): bool
    {
        $model = new SystemSettingModel();
        $row = $model->findByKey($key);
        if (! $row) {
            return false;
        }
        $saved = $model->update($row['id'], [
            'setting_value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        cache()->delete($this->cachePrefix . $key);
        cache()->delete($this->cachePrefix . 'group_' . $row['setting_group']);
        return (bool) $saved;
    }

    public function group(string $group): array
    {
        $cacheKey = $this->cachePrefix . 'group_' . $group;
        $cached = cache($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
        try {
            if (! db_connect()->tableExists('system_settings')) {
                return [];
            }
            $items = (new SystemSettingModel())
                ->where('setting_group', $group)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll();
            cache()->save($cacheKey, $items, $this->ttl);
            return $items;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function groups(): array
    {
        try {
            if (! db_connect()->tableExists('system_settings')) {
                return [];
            }
            $rows = db_connect()->table('system_settings')
                ->select('setting_group')
                ->groupBy('setting_group')
                ->orderBy('setting_group', 'ASC')
                ->get()->getResultArray();
            return array_column($rows, 'setting_group');
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function clearCache(): void
    {
        foreach ((new SystemSettingModel())->findAll() as $row) {
            cache()->delete($this->cachePrefix . $row['setting_key']);
            cache()->delete($this->cachePrefix . 'group_' . $row['setting_group']);
        }
    }

    private function castValue(?string $value, string $type)
    {
        if ($type === 'checkbox') {
            return (int) $value === 1;
        }
        if ($type === 'number') {
            return is_numeric($value) ? (int) $value : 0;
        }
        if ($type === 'json') {
            $decoded = json_decode((string) $value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return $value;
    }
}
