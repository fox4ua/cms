<?php

namespace Modules\Kernel\Services;

final class ModulePathService
{
    /**
     * System modules live in cms/system, project modules live in cms/modules.
     * Both roots use the same PSR-4 namespace prefix: Modules\\.
     */
    public function roots(): array
    {
        return [
            'system' => rtrim(ROOTPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cms' . DIRECTORY_SEPARATOR . 'system',
            'modules' => rtrim(ROOTPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cms' . DIRECTORY_SEPARATOR . 'modules',
        ];
    }

    public function manifests(): array
    {
        $files = [];
        foreach ($this->roots() as $type => $root) {
            foreach (glob($root . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'module.php') ?: [] as $file) {
                $base = realpath(dirname($file));
                if ($base === false || ! $this->insideRoot($base, $root)) {
                    continue;
                }
                $files[] = ['type' => $type, 'file' => $file, 'base' => $base];
            }
        }
        return $files;
    }

    public function moduleBase(string $machineName): ?string
    {
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]{1,99}$/', $machineName)) {
            return null;
        }
        foreach ($this->roots() as $root) {
            $base = realpath($root . DIRECTORY_SEPARATOR . $machineName);
            if ($base !== false && $this->insideRoot($base, $root) && is_file($base . DIRECTORY_SEPARATOR . 'module.php')) {
                return $base;
            }
        }
        return null;
    }

    public function manifest(string $machineName): ?string
    {
        $base = $this->moduleBase($machineName);
        return $base ? $base . DIRECTORY_SEPARATOR . 'module.php' : null;
    }

    public function moduleFile(string $machineName, string $relativePath): ?string
    {
        $base = $this->moduleBase($machineName);
        if ($base === null || $relativePath === '' || str_contains($relativePath, "\0")) {
            return null;
        }
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        if (str_starts_with($normalized, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $normalized)
            || in_array('..', explode(DIRECTORY_SEPARATOR, $normalized), true)) {
            return null;
        }

        $candidate = $base . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR);
        $resolved = realpath($candidate);
        if ($resolved === false || ! $this->insideRoot($resolved, $base)) {
            return null;
        }
        return $resolved;
    }

    private function insideRoot(string $path, string $root): bool
    {
        $root = realpath($root) ?: rtrim($root, DIRECTORY_SEPARATOR);
        $path = realpath($path) ?: $path;
        return $path === $root || str_starts_with($path, $root . DIRECTORY_SEPARATOR);
    }
}
