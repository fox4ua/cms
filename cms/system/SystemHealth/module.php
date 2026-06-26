<?php
return [
    'machine_name' => 'SystemHealth',
    'name' => 'System Health',
    'description' => 'Liveness, readiness и production readiness проверки CMS',
    'version' => '1.1.0',
    'is_system' => 1,
    'dependencies' => ['Kernel' => '1.2.0', 'Settings' => '1.1.0'],
    'install_sql' => ['Database/install.sql'],
    'routes' => [
        ['method' => 'GET', 'path' => '/admin/system/health', 'controller' => '\\Modules\\SystemHealth\\Controllers\\SystemHealthController', 'action' => 'index', 'is_admin' => 1, 'is_system' => 1],
    ],
    'menus' => [
        'admin_sidebar' => [
            ['item_key' => 'system_health.health', 'title' => 'Состояние системы', 'link_type' => 'url', 'url' => '/admin/system/health', 'icon' => 'activity', 'parent_key' => 'system', 'weight' => 33, 'is_system' => 1],
        ],
    ],
];
