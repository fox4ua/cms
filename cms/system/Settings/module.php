<?php
return [
    'machine_name' => 'Settings',
    'name' => 'Settings',
    'description' => 'Системные настройки CMS, включая управляемые HTTP security headers',
    'version' => '1.1.0',
    'is_system' => 1,
    'dependencies' => ['Kernel' => '1.2.0'],
    'update_sql' => ['1.1.0' => 'Database/Updates/1.1.0.sql'],
    'routes' => [
        ['method' => 'GET', 'path' => '/admin/settings', 'controller' => '\\Modules\\Settings\\Controllers\\SettingsController', 'action' => 'index', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'GET', 'path' => '/admin/settings/(:segment)', 'controller' => '\\Modules\\Settings\\Controllers\\SettingsController', 'action' => 'index/$1', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/settings/(:segment)', 'controller' => '\\Modules\\Settings\\Controllers\\SettingsController', 'action' => 'save/$1', 'is_admin' => 1, 'is_system' => 1],
    ],
    'menus' => [
        'admin_sidebar' => [
            ['item_key' => 'settings.cms', 'title' => 'Настройки CMS', 'link_type' => 'url', 'url' => '/admin/settings', 'icon' => 'gear', 'parent_key' => 'configuration', 'weight' => 35, 'is_system' => 1],
        ],
    ],
];
