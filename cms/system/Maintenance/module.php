<?php
return [
    'machine_name' => 'Maintenance',
    'name' => 'Maintenance',
    'description' => 'Оркестрация модульных операций обслуживания CMS',
    'version' => '1.1.0',
    'is_system' => 1,
    'dependencies' => ['Kernel' => '1.2.0'],
    'routes' => [
        ['method' => 'GET', 'path' => '/admin/system/maintenance', 'controller' => '\\Modules\\Maintenance\\Controllers\\MaintenanceController', 'action' => 'index', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/system/cleanup', 'controller' => '\\Modules\\Maintenance\\Controllers\\MaintenanceController', 'action' => 'cleanup', 'is_admin' => 1, 'is_system' => 1],
    ],
    'menus' => [
        'admin_sidebar' => [
            ['item_key' => 'maintenance.manage', 'title' => 'Обслуживание', 'link_type' => 'url', 'url' => '/admin/system/maintenance', 'icon' => 'tools', 'parent_key' => 'system', 'weight' => 34, 'is_system' => 1],
        ],
    ],
];
