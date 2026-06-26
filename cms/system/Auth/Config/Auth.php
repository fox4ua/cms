<?php

namespace Modules\Auth\Config;

use CodeIgniter\Config\BaseConfig;

class Auth extends BaseConfig
{
    public string $pepper;
    public int $maxFailedAttempts = 5;
    public int $blockBaseSeconds = 60;
    public int $blockMultiplier = 20;
    public int $rememberMeExpiry = 7776000;
    public int $rememberRotateAfter = 604800;
    public int $maxRememberTokens = 5;
    public int $sessionIdleTtl = 1800;
    public int $sessionAbsoluteTtl = 28800;
    public string $rememberCookie = 'cms_remember';
    public bool $cookieSecure;
    public string $cookieSameSite = 'Strict';

    public function __construct()
    {
        $this->pepper = (string) env('cms.auth.pepper', 'CHANGE_THIS_IN_ENV_64_BYTES_MINIMUM');
        $this->cookieSecure = filter_var(env('cms.auth.cookieSecure', true), FILTER_VALIDATE_BOOL);
        $this->maxRememberTokens = (int) env('cms.auth.maxRememberTokens', 5);
    }
}
