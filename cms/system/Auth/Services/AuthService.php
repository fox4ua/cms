<?php

namespace Modules\Auth\Services;

use Modules\Auth\Config\Auth as AuthConfig;
use Modules\Auth\Models\ActiveSessionModel;
use Modules\Auth\Models\LoginFailedModel;
use Modules\Auth\Models\UserModel;
use Modules\Auth\Models\UserTokenModel;
use Modules\Auth\Models\UserSecurityFlagModel;
use Modules\Kernel\Services\AdminActionLoggerService;

class AuthService
{
    private AuthConfig $config;

    public function __construct()
    {
        $this->config = config(AuthConfig::class);
    }

    public function login(string $email, string $password, bool $remember): array
    {
        $email = mb_strtolower(trim($email));
        $ip = (new \Modules\Kernel\Services\ClientIpResolver())->ip();
        $ua = (string) service('request')->getUserAgent();

        $pipeline = new AuthExtensionPipelineService();
        $hook = $pipeline->run('before_login_attempt', ['email' => $email, 'ip' => $ip, 'user_agent' => $ua, 'remember' => $remember]);
        if (! $hook['allowed']) {
            return $this->fail((string) $hook['message'], $hook);
        }

        if ($email === '' || $password === '') {
            return $this->fail('Введите email и пароль');
        }

        $ipCheck = (new IpAccessService())->checkLoginIp(null);
        if (! $ipCheck['allowed']) {
            return $this->fail($ipCheck['message']);
        }

        $blocked = $this->getBlock($email, $ip);
        if ($blocked) {
            return $this->fail('Слишком много попыток. Повторите после ' . $blocked);
        }

        $users = new UserModel();
        $user = $users->where('email', $email)->first();

        $valid = $user && hash_equals('active', (string) $user['status']) && password_verify($password, $user['password_hash']);

        if (! $valid) {
            $this->registerFailedAttempt($email, $ip);
            usleep(random_int(250000, 600000));
            return $this->fail('Неверный email или пароль');
        }

        $userIpCheck = (new IpAccessService())->checkLoginIp($user['id']);
        if (! $userIpCheck['allowed']) {
            return $this->fail($userIpCheck['message']);
        }

        $flags = (new UserSecurityFlagModel())->find($user['id']);
        if ($flags) {
            if (! empty($flags['login_allowed_from']) && strtotime($flags['login_allowed_from']) > time()) {
                return $this->fail('Вход для пользователя пока запрещён');
            }
            if (! empty($flags['login_allowed_until']) && strtotime($flags['login_allowed_until']) < time()) {
                return $this->fail('Срок разрешения входа истёк');
            }
        }

        $hook = $pipeline->run('after_password_verified', ['user' => $user, 'ip' => $ip, 'user_agent' => $ua, 'remember' => $remember]);
        if (! $hook['allowed']) {
            return $this->fail((string) $hook['message'], $hook);
        }

        $hook = $pipeline->run('before_login_complete', ['user' => $user, 'ip' => $ip, 'user_agent' => $ua, 'remember' => $remember]);
        if (! $hook['allowed']) {
            return $this->fail((string) $hook['message'], $hook);
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            $users->update($user['id'], ['password_hash' => $this->hashPassword($password), 'updated_at' => date('Y-m-d H:i:s')]);
        }

        $this->clearFailedAttempts($email, $ip);
        $this->startSession($user, $ip, $ua);
        $users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        if ($remember && (new SecuritySettingsService())->bool('remember_enabled')) {
            $this->issueRememberToken($user['id'], $ip, $ua);
        }

        (new AdminActionLoggerService())->log('auth.login', 'user', $user['id']);
        $pipeline->run('after_login_success', ['user' => $user, 'ip' => $ip, 'user_agent' => $ua, 'remember' => $remember]);
        return ['success' => true];
    }

    public function autoLoginFromRemember(): bool
    {
        if (session()->get('user_id')) {
            return true;
        }

        if (! (new SecuritySettingsService())->bool('remember_enabled')) {
            return false;
        }

        $cookie = $_COOKIE[$this->config->rememberCookie] ?? '';
        if (! str_contains($cookie, ':')) {
            return false;
        }

        [$selector, $plainToken] = explode(':', $cookie, 2);
        if (! preg_match('/^[a-f0-9]{24}$/', $selector) || strlen($plainToken) < 40) {
            $this->forgetRememberCookie();
            return false;
        }

        $tokens = new UserTokenModel();
        $row = $tokens->where('selector', $selector)->where('type', 'remember')->where('revoked_at', null)->first();
        if (! $row || strtotime($row['expires_at']) < time()) {
            $this->forgetRememberCookie();
            return false;
        }

        $hmac = $this->tokenHmac($plainToken);
        if (! password_verify($hmac, $row['token_hash'])) {
            if ($row) {
                $tokens->update($row['id'], ['revoked_at' => date('Y-m-d H:i:s')]);
            }
            (new AdminActionLoggerService())->suspicious('remember_token_reuse', ['selector' => $selector]);
            $this->forgetRememberCookie();
            return false;
        }

        $user = (new UserModel())->find($row['user_id']);
        if (! $user || $user['status'] !== 'active') {
            $this->forgetRememberCookie();
            return false;
        }

        $ip = (new \Modules\Kernel\Services\ClientIpResolver())->ip();
        $ua = (string) service('request')->getUserAgent();
        $this->startSession($user, $ip, $ua);
        $tokens->update($row['id'], ['last_used_at' => date('Y-m-d H:i:s')]);

        if (time() - strtotime($row['created_at']) > (new SecuritySettingsService())->int('remember_rotate_after')) {
            $tokens->update($row['id'], ['revoked_at' => date('Y-m-d H:i:s')]);
            $this->issueRememberToken($user['id'], $ip, $ua);
        }

        return true;
    }

    public function validateCurrentSession(): bool
    {
        $userId = session()->get('user_id');
        $sid = session()->get('auth_session_id');
        $hash = session()->get('auth_session_hash');
        $created = (int) session()->get('auth_created_at');
        $last = (int) session()->get('auth_last_activity');

        if (! $userId || ! $sid || ! $hash || ! $created || ! $last) {
            return false;
        }

        $settings = new SecuritySettingsService();
        if (time() - $created > $settings->int('session_absolute_ttl') || time() - $last > $settings->int('session_idle_ttl')) {
            $this->logout();
            return false;
        }

        $row = (new ActiveSessionModel())->find($sid);
        if (! $row || $row['revoked_at'] || ! hash_equals($row['session_hash'], $hash)) {
            $this->logout(false);
            return false;
        }

        $uaHash = $this->uaHash((string) service('request')->getUserAgent());
        if (! hash_equals($row['user_agent_hash'], $uaHash)) {
            (new AdminActionLoggerService())->suspicious('session_fingerprint_changed', ['session_id' => $sid]);
            $this->logout(false);
            return false;
        }

        session()->set('auth_last_activity', time());
        (new ActiveSessionModel())->update($sid, ['last_activity_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function logout(bool $revokeRemember = true): void
    {
        $sid = session()->get('auth_session_id');
        if ($sid) {
            (new ActiveSessionModel())->update($sid, ['revoked_at' => date('Y-m-d H:i:s')]);
        }
        if ($revokeRemember) {
            $this->revokeCurrentRememberToken();
        }
        session()->destroy();
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
    }

    private function startSession(array $user, string $ip, string $ua): void
    {
        session()->regenerate(true);
        $sid = bin2hex(random_bytes(16));
        $sessionSecret = bin2hex(random_bytes(32));
        $sessionHash = $this->tokenHmac($sessionSecret);
        $now = time();

        (new ActiveSessionModel())->insert([
            'id' => $sid,
            'user_id' => $user['id'],
            'session_hash' => $sessionHash,
            'ip_address' => $ip,
            'user_agent_hash' => $this->uaHash($ua),
            'created_at' => date('Y-m-d H:i:s', $now),
            'last_activity_at' => date('Y-m-d H:i:s', $now),
            'expires_at' => date('Y-m-d H:i:s', $now + (new SecuritySettingsService())->int('session_absolute_ttl')),
        ]);

        session()->set([
            'user_id' => $user['id'],
            'user' => ['id' => $user['id'], 'email' => $user['email']],
            'auth_session_id' => $sid,
            'auth_session_hash' => $sessionHash,
            'auth_created_at' => $now,
            'auth_last_activity' => $now,
        ]);
    }

    private function issueRememberToken(string $userId, string $ip, string $ua): void
    {
        $selector = bin2hex(random_bytes(12));
        $plain = bin2hex(random_bytes(32));
        $this->limitRememberTokens($userId);
        (new UserTokenModel())->insert([
            'user_id' => $userId,
            'selector' => $selector,
            'token_hash' => password_hash($this->tokenHmac($plain), PASSWORD_ARGON2ID),
            'type' => 'remember',
            'ip_address' => $ip,
            'user_agent_hash' => $this->uaHash($ua),
            'expires_at' => date('Y-m-d H:i:s', time() + (new SecuritySettingsService())->int('remember_me_expiry')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->setRememberCookie($selector . ':' . $plain, time() + (new SecuritySettingsService())->int('remember_me_expiry'));
    }

    private function limitRememberTokens(string $userId): void
    {
        $max = max(1, (int) $this->config->maxRememberTokens);
        $model = new UserTokenModel();
        $active = $model->where('user_id', $userId)->where('type', 'remember')->where('revoked_at', null)->orderBy('created_at', 'DESC')->findAll();
        foreach (array_slice($active, max(0, $max - 1)) as $old) {
            $model->update($old['id'], ['revoked_at' => date('Y-m-d H:i:s')]);
        }
    }

    private function getBlock(string $email, string $ip): ?string
    {
        $row = (new LoginFailedModel())->where('email', $email)->where('ip_address', $ip)->first();
        if ($row && $row['blocked_until'] && strtotime($row['blocked_until']) > time()) {
            return date('H:i:s', strtotime($row['blocked_until']));
        }
        return null;
    }

    private function registerFailedAttempt(string $email, string $ip): void
    {
        $model = new LoginFailedModel();
        $row = $model->where('email', $email)->where('ip_address', $ip)->first();
        $attempts = $row ? ((int) $row['attempts'] + 1) : 1;
        $blockedUntil = null;
        $settings = new SecuritySettingsService();
        if ($attempts >= $settings->int('max_failed_attempts')) {
            $penalty = min(86400, (2 ** max(0, $attempts - $settings->int('max_failed_attempts'))) * $settings->int('block_multiplier') * $settings->int('block_base_seconds'));
            $blockedUntil = date('Y-m-d H:i:s', time() + $penalty);
        }
        $data = ['email' => $email, 'ip_address' => $ip, 'attempts' => $attempts, 'blocked_until' => $blockedUntil, 'last_attempt_at' => date('Y-m-d H:i:s')];
        $row ? $model->update($row['id'], $data) : $model->insert($data);
    }

    private function clearFailedAttempts(string $email, string $ip): void
    {
        db_connect()->table('user_login_failed')->where('email', $email)->where('ip_address', $ip)->delete();
    }

    private function revokeCurrentRememberToken(): void
    {
        $cookie = $_COOKIE[$this->config->rememberCookie] ?? '';
        if (str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);
            (new UserTokenModel())->where('selector', $selector)->set(['revoked_at' => date('Y-m-d H:i:s')])->update();
        }
        $this->forgetRememberCookie();
    }

    private function setRememberCookie(string $value, int $expires): void
    {
        setcookie($this->config->rememberCookie, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $this->config->cookieSecure,
            'httponly' => true,
            'samesite' => $this->config->cookieSameSite,
        ]);
    }

    private function forgetRememberCookie(): void
    {
        $this->setRememberCookie('', time() - 3600);
    }

    private function tokenHmac(string $token): string
    {
        if ($this->config->pepper === 'CHANGE_THIS_IN_ENV_64_BYTES_MINIMUM' || strlen($this->config->pepper) < 32) {
            throw new \RuntimeException('Auth pepper не настроен. Укажите cms.auth.pepper в .env.');
        }
        return hash_hmac('sha256', $token, $this->config->pepper);
    }

    private function uaHash(string $ua): string
    {
        return hash('sha256', substr($ua, 0, 255));
    }

    private function fail(string $message, array $extra = []): array
    {
        return array_merge(['success' => false, 'message' => $message], $extra);
    }
}
