<?php

namespace Modules\Kernel\Services;

class AdminGuardService
{
    public function assertAuthenticated(): void
    {
        $provider = (new AuthenticationProviderResolver())->provider();
        if ($provider === null || ! $provider->check()) {
            $this->deny();
        }
    }

    public function enforcePostLoginRequirements(): void
    {
        $provider = (new AuthenticationProviderResolver())->provider();
        if ($provider === null) {
            return;
        }
        $path = service('request')->getUri()->getPath();
        $provider->enforcePostLoginRequirements($path);
    }

    private function deny(): void
    {
        redirect()->to(site_url('login'))->send();
        exit;
    }
}
