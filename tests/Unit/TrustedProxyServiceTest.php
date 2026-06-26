<?php

declare(strict_types=1);

use Modules\Kernel\Services\TrustedProxyService;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/cms/system/Kernel/Services/TrustedProxyService.php';

final class TrustedProxyServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
    }

    public function testIgnoresForwardedHeadersFromUntrustedPeer(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.5';
        $service = new TrustedProxyService(['10.0.0.0/8'], ['x-forwarded-for']);
        self::assertSame('203.0.113.10', $service->clientIp());
    }

    public function testResolvesClientThroughTrustedProxyChain(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.2';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.5, 10.0.0.1';
        $service = new TrustedProxyService(['10.0.0.0/8'], ['x-forwarded-for']);
        self::assertSame('198.51.100.5', $service->clientIp());
    }
}
