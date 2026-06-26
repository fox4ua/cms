<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\UserSecurityFlagModel;
use Modules\Kernel\Contracts\AuthenticationProviderInterface;

final class AuthAuthenticationProvider implements AuthenticationProviderInterface
{
    public function check(): bool
    {
        return (new AuthService())->validateCurrentSession();
    }

    public function user(): ?array
    {
        $user = session()->get('user');
        return is_array($user) ? $user : null;
    }

    public function logout(): void
    {
        (new AuthService())->logout();
    }

    public function hasRememberLogin(): bool
    {
        return (bool) service('request')->getCookie('remember_me');
    }

    public function enforcePostLoginRequirements(string $currentPath): void
    {
        $userId = (string) session()->get('user_id');
        if ($userId === '') {
            return;
        }
        $flags = (new UserSecurityFlagModel())->find($userId);
        $mustChangePassword = ($flags && (int) $flags['force_password_change'] === 1)
            || (new PasswordPolicyService())->passwordExpired($userId);

        if ($mustChangePassword && trim($currentPath, '/') !== 'admin/auth/password') {
            redirect()->to(site_url('admin/auth/password'))->send();
            exit;
        }
    }
}
