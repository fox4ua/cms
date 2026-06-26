<?php

namespace Modules\Kernel\Services;

final class SqlStatementParser
{
    public function parse(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $quote = null;
        $lineComment = false;
        $blockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($lineComment) {
                if ($char === "\n") {
                    $lineComment = false;
                    $buffer .= $char;
                }
                continue;
            }
            if ($blockComment) {
                if ($char === '*' && $next === '/') {
                    $blockComment = false;
                    $i++;
                }
                continue;
            }
            if ($quote === null && $char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) {
                $lineComment = true;
                $i++;
                continue;
            }
            if ($quote === null && $char === '#') {
                $lineComment = true;
                continue;
            }
            if ($quote === null && $char === '/' && $next === '*') {
                $blockComment = true;
                $i++;
                continue;
            }

            if ($quote !== null) {
                $buffer .= $char;
                if ($char === '\\' && $i + 1 < $length) {
                    $buffer .= $sql[++$i];
                    continue;
                }
                if ($char === $quote) {
                    if ($next === $quote) {
                        $buffer .= $sql[++$i];
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }

            if (in_array($char, ["'", '"', '`'], true)) {
                $quote = $char;
                $buffer .= $char;
                continue;
            }
            if ($char === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }
            $buffer .= $char;
        }

        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }
        return $statements;
    }
}
