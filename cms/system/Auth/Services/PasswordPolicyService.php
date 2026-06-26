<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\PasswordHistoryModel;
use Modules\Auth\Models\UserSecurityFlagModel;

class PasswordPolicyService
{
    public function validate(string $password, ?string $userId = null): array
    {
        $s = new SecuritySettingsService();
        $errors = [];
        if (mb_strlen($password) < $s->int('password_min_length')) $errors[] = 'Минимальная длина пароля: ' . $s->int('password_min_length');
        if ($s->bool('password_require_upper') && ! preg_match('/\p{Lu}/u', $password)) $errors[] = 'Нужна хотя бы одна большая буква';
        if ($s->bool('password_require_lower') && ! preg_match('/\p{Ll}/u', $password)) $errors[] = 'Нужна хотя бы одна маленькая буква';
        if ($s->bool('password_require_digit') && ! preg_match('/\d/', $password)) $errors[] = 'Нужна хотя бы одна цифра';
        if ($s->bool('password_require_special') && ! preg_match('/[^\p{L}\p{N}]/u', $password)) $errors[] = 'Нужен хотя бы один спецсимвол';

        if ($userId && $s->int('password_history_count') > 0) {
            $history = (new PasswordHistoryModel())->where('user_id', $userId)->orderBy('id', 'DESC')->findAll($s->int('password_history_count'));
            foreach ($history as $row) {
                if (password_verify($password, $row['password_hash'])) { $errors[] = 'Нельзя использовать один из последних паролей'; break; }
            }
        }
        return ['valid' => $errors === [], 'errors' => $errors];
    }

    public function rememberPassword(string $userId, string $hash): void
    {
        (new PasswordHistoryModel())->insert(['user_id' => $userId, 'password_hash' => $hash, 'created_at' => date('Y-m-d H:i:s')]);
        (new UserSecurityFlagModel())->save(['user_id' => $userId, 'force_password_change' => 0, 'password_changed_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function passwordExpired(string $userId): bool
    {
        $days = (new SecuritySettingsService())->int('password_expires_days');
        if ($days <= 0) return false;
        $flags = (new UserSecurityFlagModel())->find($userId);
        $date = $flags['password_changed_at'] ?? null;
        if (! $date) return true;
        return strtotime($date) < time() - ($days * 86400);
    }
}
