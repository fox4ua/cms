<?php

namespace Modules\ModuleManager\Services;

use Modules\Kernel\Services\SqlStatementParser;
use RuntimeException;

final class ModuleSqlRunnerService
{
    public function validateFile(string $file): ?string
    {
        if (! is_file($file)) {
            return 'SQL-файл не найден: ' . $file;
        }
        $sql = file_get_contents($file);
        if ($sql === false) {
            return 'Не удалось прочитать SQL-файл: ' . $file;
        }
        if (stripos($sql, 'DELIMITER') !== false || preg_match('~CREATE\s+(PROCEDURE|FUNCTION|TRIGGER|EVENT)~i', $sql)) {
            return 'Сложные SQL-скрипты с DELIMITER/procedure/function/trigger/event запрещены.';
        }
        if (preg_match('~\b(DROP\s+DATABASE|GRANT\s+|REVOKE\s+|LOAD_FILE\s*\(|INTO\s+OUTFILE)\b~i', $sql)) {
            return 'SQL содержит запрещённые команды высокого риска.';
        }
        try {
            $statements = (new SqlStatementParser())->parse($sql);
        } catch (\Throwable $e) {
            return 'Не удалось разобрать SQL: ' . $e->getMessage();
        }
        if ($statements === []) {
            return 'SQL-файл не содержит исполняемых выражений.';
        }
        return null;
    }

    public function runFile(string $file): void
    {
        $error = $this->validateFile($file);
        if ($error !== null) {
            throw new RuntimeException($error);
        }

        $sql = (string) file_get_contents($file);
        $statements = (new SqlStatementParser())->parse($sql);
        $db = db_connect();
        foreach ($statements as $statement) {
            $db->query($statement);
        }
    }
}
