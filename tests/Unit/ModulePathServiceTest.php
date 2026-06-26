<?php

declare(strict_types=1);

use Modules\Kernel\Services\ModulePathService;
use PHPUnit\Framework\TestCase;

final class ModulePathServiceTest extends TestCase
{
    public function testRejectsTraversalOutsideModule(): void
    {
        if (! defined('ROOTPATH')) {
            self::markTestSkipped('Run inside the complete CodeIgniter test bootstrap.');
        }
        self::assertNull((new ModulePathService())->moduleFile('Menu', '../../.env'));
    }
}
