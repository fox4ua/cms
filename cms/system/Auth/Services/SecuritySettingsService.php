<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\AuthSettingModel;

class SecuritySettingsService
{
    private array $defaults = [
        'max_failed_attempts' => '5',
        'block_base_seconds' => '60',
        'block_multiplier' => '20',
        'session_idle_ttl' => '1800',
        'session_absolute_ttl' => '43200',
        'remember_enabled' => '1',
        'remember_me_expiry' => '7776000',
        'remember_rotate_after' => '604800',
        'password_min_length' => '14',
        'password_require_upper' => '1',
        'password_require_lower' => '1',
        'password_require_digit' => '1',
        'password_require_special' => '1',
        'password_history_count' => '5',
        'password_expires_days' => '180',
        'captcha_after_attempts' => '3',
        'captcha_enabled' => '0',
        'two_factor_mode' => 'off',
        'two_factor_skip_internal_ip' => '1',
        'captcha_skip_internal_ip' => '1',
        'admin_ip_allowlist_enabled' => '0',
        'internal_ip_ranges' => "10.0.0.0/8\n172.16.0.0/12\n192.168.0.0/16\n127.0.0.1/32\n::1/128",
    ];

    public function all(): array
    {
        $rows = (new AuthSettingModel())->findAll();
        $values = $this->defaults;
        foreach ($rows as $row) {
            $values[$row['setting_key']] = (string) $row['setting_value'];
        }
        return $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        return $settings[$key] ?? $default;
    }

    public function save(array $data): void
    {
        $model = new AuthSettingModel();
        foreach ($this->defaults as $key => $default) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $value = is_array($data[$key]) ? implode("\n", $data[$key]) : (string) $data[$key];
            $row = $model->where('setting_key', $key)->first();
            $payload = ['setting_key' => $key, 'setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')];
            $row ? $model->update($row['id'], $payload) : $model->insert($payload);
        }
    }

    public function int(string $key): int { return (int) $this->get($key, $this->defaults[$key] ?? 0); }
    public function bool(string $key): bool { return in_array((string) $this->get($key, '0'), ['1', 'true', 'yes', 'on'], true); }
}
