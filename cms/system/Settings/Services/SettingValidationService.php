<?php

namespace Modules\Settings\Services;

use InvalidArgumentException;

class SettingValidationService
{
    public function validate(array $setting, string $value): string
    {
        $type = (string) ($setting['field_type'] ?? 'text');
        $required = (int) ($setting['is_required'] ?? 0) === 1;
        $key = (string) ($setting['setting_key'] ?? '');

        if ($required && trim($value) === '') {
            throw new InvalidArgumentException('Поле обязательно: ' . ($setting['setting_label'] ?? $key));
        }

        if ($type === 'number') {
            if (! is_numeric($value)) {
                throw new InvalidArgumentException('Поле должно быть числом: ' . ($setting['setting_label'] ?? $key));
            }
            $n = (int) $value;
            if (isset($setting['min_value']) && $setting['min_value'] !== null && $setting['min_value'] !== '' && $n < (int) $setting['min_value']) {
                throw new InvalidArgumentException('Значение меньше минимума: ' . ($setting['setting_label'] ?? $key));
            }
            if (isset($setting['max_value']) && $setting['max_value'] !== null && $setting['max_value'] !== '' && $n > (int) $setting['max_value']) {
                throw new InvalidArgumentException('Значение больше максимума: ' . ($setting['setting_label'] ?? $key));
            }
            return (string) $n;
        }

        if ($type === 'select') {
            $options = json_decode((string) ($setting['field_options'] ?? ''), true) ?: [];
            if ($options && ! array_key_exists($value, $options)) {
                throw new InvalidArgumentException('Недопустимое значение: ' . ($setting['setting_label'] ?? $key));
            }
        }

        if ($type === 'json' && trim($value) !== '') {
            json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Некорректный JSON: ' . ($setting['setting_label'] ?? $key));
            }
        }

        $rule = trim((string) ($setting['validation_rule'] ?? ''));
        if ($rule !== '' && trim($value) !== '') {
            if ($rule === 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Некорректный email: ' . ($setting['setting_label'] ?? $key));
            }
            if ($rule === 'timezone' && ! in_array($value, timezone_identifiers_list(), true)) {
                throw new InvalidArgumentException('Некорректный часовой пояс: ' . ($setting['setting_label'] ?? $key));
            }
            if (in_array($rule, ['header', 'csp'], true)) {
                if (str_contains($value, "\r") || str_contains($value, "\n") || str_contains($value, "\0")) {
                    throw new InvalidArgumentException('HTTP-заголовок содержит недопустимые управляющие символы: ' . ($setting['setting_label'] ?? $key));
                }
                if (strlen($value) > 8000) {
                    throw new InvalidArgumentException('HTTP-заголовок слишком длинный: ' . ($setting['setting_label'] ?? $key));
                }
                if ($rule === 'csp' && trim($value) !== '' && ! str_contains($value, "default-src")) {
                    throw new InvalidArgumentException('CSP должна содержать директиву default-src.');
                }
            }
            if (str_starts_with($rule, 'regex:')) {
                $pattern = substr($rule, 6);
                if (@preg_match($pattern, '') === false || ! preg_match($pattern, $value)) {
                    throw new InvalidArgumentException('Некорректный формат: ' . ($setting['setting_label'] ?? $key));
                }
            }
        }

        return $value;
    }
}
