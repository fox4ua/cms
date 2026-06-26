<?php

namespace Modules\Kernel\Services;

final class SchemaVersionService
{
    public const CODE_VERSION = '1.4.0';

    public function current(): ?string
    {
        try {
            $db = db_connect();
            if (! $db->tableExists('cms_schema_updates')) {
                return null;
            }
            $rows = $db->table('cms_schema_updates')
                ->select('version')
                ->where('status', 'success')
                ->get()
                ->getResultArray();
            $versions = array_values(array_filter(array_map(
                static fn (array $row): string => trim((string) ($row['version'] ?? '')),
                $rows
            ), static fn (string $version): bool => preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?$/', $version) === 1));
            if ($versions === []) {
                return null;
            }
            usort($versions, 'version_compare');
            return (string) end($versions);
        } catch (\Throwable) {
            return null;
        }
    }

    public function updateRequired(): bool
    {
        $current = $this->current();
        return $current === null || version_compare($current, self::CODE_VERSION, '<');
    }
}
