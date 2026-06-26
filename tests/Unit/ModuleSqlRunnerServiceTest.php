<?php

declare(strict_types=1);

use Modules\ModuleManager\Services\ModuleSqlRunnerService;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/cms/system/Kernel/Services/SqlStatementParser.php';
require_once dirname(__DIR__, 2) . '/cms/system/ModuleManager/Services/ModuleSqlRunnerService.php';

final class ModuleSqlRunnerServiceTest extends TestCase
{
    public function testRejectsAdministrativeSql(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'cms-sql-');
        file_put_contents($file, "DROP DATABASE production;");
        try {
            self::assertNotNull((new ModuleSqlRunnerService())->validateFile($file));
        } finally {
            @unlink($file);
        }
    }

    public function testAcceptsSimpleSql(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'cms-sql-');
        file_put_contents($file, "SELECT 1;");
        try {
            self::assertNull((new ModuleSqlRunnerService())->validateFile($file));
        } finally {
            @unlink($file);
        }
    }
}
