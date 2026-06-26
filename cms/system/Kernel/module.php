<?php
return [
    'machine_name' => 'Kernel',
    'name' => 'Kernel',
    'description' => 'Минимальное ядро CMS: bootstrap, contracts, admin shell, security and infrastructure services',
    'version' => '1.2.0',
    'is_system' => 1,
    'dependencies' => [],
    'maintenance_providers' => ['\\Modules\\Kernel\\Services\\KernelCleanupProvider'],
    'routes' => [],
    'menus' => [],
];
