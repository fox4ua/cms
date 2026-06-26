<?php

namespace Modules\Kernel\Contracts;

interface MaintenanceProviderInterface
{
    public function key(): string;
    public function label(): string;
    public function run(): array;
}
