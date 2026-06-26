<?php

namespace Modules\Kernel\Contracts;

interface AuthenticationProviderInterface
{
    public function check(): bool;

    public function user(): ?array;

    public function logout(): void;

    public function hasRememberLogin(): bool;

    /**
     * Called after session validation for admin pages.
     * Provider may redirect or deny when password change, MFA, or another challenge is required.
     */
    public function enforcePostLoginRequirements(string $currentPath): void;
}
