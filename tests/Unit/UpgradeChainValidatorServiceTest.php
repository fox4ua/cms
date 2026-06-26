<?php

declare(strict_types=1);

use Modules\Kernel\Services\UpgradeChainValidatorService;
use PHPUnit\Framework\TestCase;

final class UpgradeChainValidatorServiceTest extends TestCase
{
    public function testReleaseUpgradeChainIsValid(): void
    {
        if (! defined('ROOTPATH')) {
            self::markTestSkipped('Run inside the complete CodeIgniter test bootstrap.');
        }
        $result = (new UpgradeChainValidatorService())->validate();
        self::assertTrue($result['ok'], implode('; ', $result['errors']));
    }
}
