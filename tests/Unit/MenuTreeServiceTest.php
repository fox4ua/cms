<?php

declare(strict_types=1);

use Modules\Menu\Services\MenuTreeService;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/cms/system/Menu/Services/MenuTreeService.php';

final class MenuTreeServiceTest extends TestCase
{
    public function testBuildsHierarchy(): void
    {
        $tree = (new MenuTreeService())->build([
            ['item_key' => 'root', 'parent_key' => '', 'title' => 'Root'],
            ['item_key' => 'child', 'parent_key' => 'root', 'title' => 'Child'],
        ]);

        self::assertCount(1, $tree);
        self::assertSame('root', $tree[0]['item_key']);
        self::assertSame('child', $tree[0]['children'][0]['item_key']);
    }

    public function testOrphanBecomesRoot(): void
    {
        $tree = (new MenuTreeService())->build([
            ['item_key' => 'orphan', 'parent_key' => 'missing', 'title' => 'Orphan'],
        ]);
        self::assertSame('orphan', $tree[0]['item_key']);
    }
}
