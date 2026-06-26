<?php

declare(strict_types=1);

use Modules\Kernel\Services\RequestPathService;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/cms/system/Kernel/Services/RequestPathService.php';

final class RequestPathServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
    }

    public function testStripsPublicIndexPhpPrefix(): void
    {
        $_SERVER['REQUEST_URI'] = '/public/index.php/admin/modules?x=1';
        $_SERVER['SCRIPT_NAME'] = '/public/index.php';
        self::assertSame('/admin/modules', (new RequestPathService())->path());
    }

    public function testStripsPublicDirectoryForRewrittenUrls(): void
    {
        $_SERVER['REQUEST_URI'] = '/public/admin/modules';
        $_SERVER['SCRIPT_NAME'] = '/public/index.php';
        self::assertSame('/admin/modules', (new RequestPathService())->path());
    }
}
