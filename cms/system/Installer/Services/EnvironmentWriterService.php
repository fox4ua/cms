<?php

namespace Modules\Installer\Services;

use RuntimeException;

final class EnvironmentWriterService
{
    public function write(array $values): void
    {
        $path = ROOTPATH . '.env';
        $lines = is_file($path) ? file($path, FILE_IGNORE_NEW_LINES) ?: [] : [];
        $replace = [];
        foreach ($values as $key => $value) {
            $replace[(string) $key] = (string) $value;
        }

        $output = [];
        $seen = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*([A-Za-z0-9_.-]+)\s*=/', $line, $match) && array_key_exists($match[1], $replace)) {
                $output[] = $match[1] . ' = ' . $this->quote($replace[$match[1]]);
                $seen[$match[1]] = true;
                continue;
            }
            $output[] = $line;
        }

        foreach ($replace as $key => $value) {
            if (! isset($seen[$key])) {
                $output[] = $key . ' = ' . $this->quote($value);
            }
        }

        $temp = $path . '.tmp.' . bin2hex(random_bytes(6));
        if (file_put_contents($temp, implode(PHP_EOL, $output) . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Не удалось записать временный .env.');
        }
        @chmod($temp, 0600);
        if (! @rename($temp, $path)) {
            @unlink($temp);
            throw new RuntimeException('Не удалось атомарно заменить .env.');
        }
        @chmod($path, 0600);
    }

    private function quote(string $value): string
    {
        $value = str_replace(["\\", '"', "\r", "\n"], ["\\\\", '\\"', '', '\\n'], $value);
        return '"' . $value . '"';
    }
}
