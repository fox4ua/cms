<?php
namespace Modules\Auth\Services;

class AuthHookNames
{
    public const ALLOWED = [
        'before_login_form',
        'before_login_attempt',
        'after_failed_login',
        'after_password_verified',
        'before_login_complete',
        'after_login_success',
        'before_logout',
    ];

    public static function isAllowed(string $name): bool
    {
        return in_array($name, self::ALLOWED, true);
    }
}
