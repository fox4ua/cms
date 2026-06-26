<?php

namespace Modules\Kernel\Services;

final class UpgradeChainValidatorService
{
    public function validate(): array
    {
        $errors = [];
        $warnings = [];
        $root = $this->scanDirectory(ROOTPATH . 'sql/updates', 'CMS', $errors, $warnings);
        $modules = [];

        foreach ((new ModulePathService())->manifests() as $manifestInfo) {
            $manifest = include $manifestInfo['file'];
            if (! is_array($manifest)) {
                $errors[] = 'Некорректный module.php: ' . $manifestInfo['file'];
                continue;
            }
            $machine = (string) ($manifest['machine_name'] ?? basename($manifestInfo['base']));
            $declared = (array) ($manifest['update_sql'] ?? []);
            $seen = [];
            foreach ($declared as $version => $relative) {
                $version = (string) $version;
                if (! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
                    $errors[] = $machine . ': некорректная версия update_sql ' . $version;
                    continue;
                }
                if (isset($seen[$version])) {
                    $errors[] = $machine . ': дублируется update_sql версии ' . $version;
                }
                $seen[$version] = true;
                $file = $manifestInfo['base'] . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $relative);
                if (! is_readable($file) || filesize($file) === 0) {
                    $errors[] = $machine . ': update-файл отсутствует или пуст: ' . $relative;
                    continue;
                }
                $this->inspectSql($file, $machine . ' ' . $version, $errors, $warnings);
            }
            if ($declared !== []) {
                $versions = array_keys($declared);
                usort($versions, 'version_compare');
                $modules[$machine] = $versions;
            }
        }

        return [
            'ok' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'versions' => array_keys($root),
            'module_versions' => $modules,
            'count' => count($root) + array_sum(array_map('count', $modules)),
        ];
    }

    private function scanDirectory(string $directory, string $scope, array &$errors, array &$warnings): array
    {
        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        $versions = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (! preg_match('/^(\d+\.\d+\.\d+)(?:[-_].+)?\.sql$/', $name, $match)) {
                $warnings[] = $scope . ': файл обновления имеет нестандартное имя: ' . $name;
                continue;
            }
            $version = $match[1];
            if (isset($versions[$version])) {
                $errors[] = $scope . ': дублируется версия ' . $version . ': ' . basename($versions[$version]) . ' и ' . $name;
            }
            $versions[$version] = $file;
            if (! is_readable($file) || filesize($file) === 0) {
                $errors[] = $scope . ': файл недоступен или пуст: ' . $name;
                continue;
            }
            $this->inspectSql($file, $scope . ' ' . $version, $errors, $warnings);
        }
        uksort($versions, 'version_compare');
        return $versions;
    }

    private function inspectSql(string $file, string $label, array &$errors, array &$warnings): void
    {
        $sql = file_get_contents($file) ?: '';
        if (preg_match('/\b(DROP\s+DATABASE|GRANT\s+ALL|CREATE\s+USER|ALTER\s+USER)\b/i', $sql)) {
            $errors[] = $label . ': обнаружена запрещённая административная SQL-операция.';
        }
        if (preg_match('/\b(TRUNCATE\s+TABLE\s+(users|cms_modules|cms_routes))\b/i', $sql)) {
            $errors[] = $label . ': обнаружена опасная очистка системной таблицы.';
        }
        if (preg_match('/\bDELIMITER\b|CREATE\s+(PROCEDURE|FUNCTION|TRIGGER)\b/i', $sql)) {
            $warnings[] = $label . ': сложный SQL не поддерживается стандартным SQL runner.';
        }
        if (substr_count($sql, "'") % 2 !== 0) {
            $warnings[] = $label . ': возможно, несбалансированы одинарные кавычки.';
        }
    }
}
