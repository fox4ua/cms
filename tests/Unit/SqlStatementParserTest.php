<?php

declare(strict_types=1);

use Modules\Installer\Services\SqlStatementParser;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/cms/system/Kernel/Services/SqlStatementParser.php';
require_once dirname(__DIR__, 2) . '/cms/system/Installer/Services/SqlStatementParser.php';

final class SqlStatementParserTest extends TestCase
{
    public function testSplitsStatementsOutsideQuotesAndComments(): void
    {
        $sql = "-- comment\nCREATE TABLE x (v VARCHAR(20)); INSERT INTO x VALUES ('a;b'); /* c; */ UPDATE x SET v=\"z;y\";";
        $items = (new SqlStatementParser())->parse($sql);
        self::assertCount(3, $items);
        self::assertStringContainsString("'a;b'", $items[1]);
    }
}
