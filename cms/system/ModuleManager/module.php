<?php
return [
    'machine_name' => 'ModuleManager',
    'name' => 'Module Manager',
    'description' => 'Установка, обновление, включение, отключение и аудит операций модулей CMS',
    'version' => '1.5.0',
    'is_system' => 1,
    'dependencies' => ['Kernel' => '1.2.0'],
    'maintenance_providers' => ['\\Modules\\ModuleManager\\Services\\ModuleManagerCleanupProvider'],
    'install_sql' => ['Database/install.sql'],
    'update_sql' => [
        '1.1.0' => 'Database/Updates/1.1.0.sql',
        '1.2.0' => 'Database/Updates/1.2.0.sql',
        '1.3.0' => 'Database/Updates/1.3.0.sql',
        '1.5.0' => 'Database/Updates/1.5.0.sql',
    ],
    'routes' => [
        ['method' => 'GET', 'path' => '/admin/modules', 'controller' => '\\Modules\\ModuleManager\\Controllers\\ModuleManagerController', 'action' => 'index', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'GET', 'path' => '/admin/modules/preflight/(:num)/(:segment)', 'controller' => '\\Modules\\ModuleManager\\Controllers\\ModuleManagerController', 'action' => 'preflight/$1/$2', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'GET', 'path' => '/admin/modules/operations', 'controller' => '\\Modules\\ModuleManager\\Controllers\\ModuleManagerController', 'action' => 'operations', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'GET', 'path' => '/admin/modules/locks', 'controller' => '\\Modules\\ModuleManager\\Controllers\\ModuleManagerController', 'action' => 'locks', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/modules/locks/force/(:segment)', 'controller' => '\\Modules\\ModuleManager\\Controllers\\ModuleManagerController', 'action' => 'forceUnlock/$1', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/modules/sync', 'controller' => '\\Modules\\ModuleManager\\Controllers\\ModuleManagerController', 'action' => 'sync', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/modules/install/(:num)', 'controller' => '\\Modules\\ModuleManager\\Controllers\\ModuleManagerController', 'action' => 'install/$1', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/modules/update/(:num)', 'controller' => '\\Modules\\ModuleManager\\Controllers\\ModuleManagerController', 'action' => 'update/$1', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/modules/enable/(:num)', 'controller' => '\\Modules\\ModuleManager\\Controllers\\ModuleManagerController', 'action' => 'enable/$1', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/modules/disable/(:num)', 'controller' => '\\Modules\\ModuleManager\\Controllers\\ModuleManagerController', 'action' => 'disable/$1', 'is_admin' => 1, 'is_system' => 1],
    ],
    'menus' => [
        'admin_sidebar' => [
            ['item_key' => 'module_manager.modules', 'title' => 'Модули', 'link_type' => 'url', 'url' => '/admin/modules', 'icon' => 'boxes', 'parent_key' => 'system', 'weight' => 20, 'is_system' => 1],
            ['item_key' => 'module_manager.operations', 'title' => 'Операции модулей', 'link_type' => 'url', 'url' => '/admin/modules/operations', 'icon' => 'clock-history', 'parent_key' => 'system', 'weight' => 21, 'is_system' => 1],
            ['item_key' => 'module_manager.locks', 'title' => 'Блокировки', 'link_type' => 'url', 'url' => '/admin/modules/locks', 'icon' => 'lock', 'parent_key' => 'system', 'weight' => 22, 'is_system' => 1],
        ],
    ],
];
