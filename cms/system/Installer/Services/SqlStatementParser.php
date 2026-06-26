<?php

namespace Modules\Installer\Services;

/** @deprecated Use Modules\Kernel\Services\SqlStatementParser. */
final class SqlStatementParser
{
    public function parse(string $sql): array
    {
        return (new \Modules\Kernel\Services\SqlStatementParser())->parse($sql);
    }
}
