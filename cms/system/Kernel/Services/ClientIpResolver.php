<?php

namespace Modules\Kernel\Services;

final class ClientIpResolver
{
    public function ip(): string
    {
        return (new TrustedProxyService())->clientIp();
    }
}
