<?php

namespace Modules\ModuleManager\Services;

use Modules\Kernel\Services\ClientIpResolver;
use Modules\ModuleManager\Models\CmsLockModel;
use RuntimeException;
use Throwable;

final class ModuleLockService
{
    /** @var array<string,array{token:string,legacy:bool,owner:string}> */
    private array $held = [];
    private bool $shutdownRegistered = false;

    public function acquire(string $key, int $ttl = 300, ?string $operation = null): string
    {
        $ttl = max(30, min(3600, $ttl));
        $this->clearStale();
        $token = bin2hex(random_bytes(32));
        $owner = (string) (session()->get('user_id') ?: (new ClientIpResolver())->ip());
        $now = date('Y-m-d H:i:s');
        $fields = $this->fields();
        $legacy = ! in_array('lock_token', $fields, true);

        $data = [
            'lock_key' => $key,
            'owner' => $owner,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
            'created_at' => $now,
        ];
        if (! $legacy) {
            $data['lock_token'] = $token;
        }
        if (in_array('operation', $fields, true)) {
            $data['operation'] = $operation ?: $key;
        }
        if (in_array('updated_at', $fields, true)) {
            $data['updated_at'] = $now;
        }

        try {
            db_connect()->table('cms_locks')->insert($data);
        } catch (Throwable) {
            throw new RuntimeException('Операция уже выполняется. Проверьте активные блокировки.');
        }

        $this->held[$key] = ['token' => $token, 'legacy' => $legacy, 'owner' => $owner];
        $this->registerShutdownRelease();
        return $token;
    }

    public function refresh(string $key, ?string $token = null, int $ttl = 300): bool
    {
        $held = $this->held[$key] ?? null;
        $token ??= $held['token'] ?? null;
        if ($token === null) {
            return false;
        }
        $builder = db_connect()->table('cms_locks')->where('lock_key', $key);
        if (! ($held['legacy'] ?? false) && in_array('lock_token', $this->fields(), true)) {
            $builder->where('lock_token', $token);
        } elseif ($held !== null) {
            $builder->where('owner', $held['owner']);
        }
        $data = ['expires_at' => date('Y-m-d H:i:s', time() + max(30, min(3600, $ttl)))];
        if (in_array('updated_at', $this->fields(), true)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return (bool) $builder->update($data);
    }

    public function release(string $key, ?string $token = null): void
    {
        $held = $this->held[$key] ?? null;
        $token ??= $held['token'] ?? null;
        if ($token === null) {
            return;
        }
        try {
            $builder = db_connect()->table('cms_locks')->where('lock_key', $key);
            if ($held !== null && ($held['legacy'] || ! in_array('lock_token', $this->fields(), true))) {
                $builder->where('owner', $held['owner']);
            } else {
                $builder->where('lock_token', $token);
            }
            $builder->delete();
        } catch (Throwable) {
        }
        unset($this->held[$key]);
    }

    public function forceRelease(string $key): bool
    {
        try {
            return db_connect()->table('cms_locks')->where('lock_key', $key)->delete();
        } catch (Throwable) {
            return false;
        }
    }

    public function clearStale(): int
    {
        try {
            return (int) db_connect()->table('cms_locks')->where('expires_at <', date('Y-m-d H:i:s'))->delete();
        } catch (Throwable) {
            return 0;
        }
    }

    public function active(): array
    {
        $this->clearStale();
        try {
            return db_connect()->table('cms_locks')->orderBy('created_at', 'DESC')->get()->getResultArray();
        } catch (Throwable) {
            return [];
        }
    }

    private function fields(): array
    {
        try {
            return db_connect()->getFieldNames('cms_locks');
        } catch (Throwable) {
            return [];
        }
    }

    private function registerShutdownRelease(): void
    {
        if ($this->shutdownRegistered) {
            return;
        }
        $this->shutdownRegistered = true;
        register_shutdown_function(function (): void {
            foreach (array_keys($this->held) as $key) {
                $this->release((string) $key);
            }
        });
    }
}
