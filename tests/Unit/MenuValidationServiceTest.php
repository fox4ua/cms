<?php

declare(strict_types=1);

use Modules\Menu\Services\MenuValidationService;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/cms/system/Menu/Services/MenuValidationService.php';

final class MenuValidationServiceTest extends TestCase
{
    public function testDetectsCycle(): void
    {
        $items = [
            ['item_key' => 'a', 'parent_key' => ''],
            ['item_key' => 'b', 'parent_key' => 'a'],
            ['item_key' => 'c', 'parent_key' => 'b'],
        ];
        self::assertTrue((new MenuValidationService())->wouldCreateCycle($items, 'a', 'c'));
    }

    public function testRejectsExcessiveDepth(): void
    {
        $items = [
            ['item_key' => 'a', 'parent_key' => ''],
            ['item_key' => 'b', 'parent_key' => 'a'],
            ['item_key' => 'c', 'parent_key' => 'b'],
            ['item_key' => 'd', 'parent_key' => 'c'],
        ];
        self::assertTrue((new MenuValidationService())->exceedsMaxDepth($items, 'e', 'd', 4));
    }
}
