<?php

namespace Modules\Kernel\Services;

final class BootstrapStatus
{
    public function __construct(
        public bool $maintenance = false,
        public bool $databaseAvailable = true,
        public ?string $errorCode = null,
        public ?string $message = null,
        public bool $installerRequired = false,
        public bool $updateRequired = false
    ) {}

    public function canUseDatabaseRoutes(): bool
    {
        return ! $this->maintenance
            && $this->databaseAvailable
            && ! $this->installerRequired
            && ! $this->updateRequired;
    }
}
