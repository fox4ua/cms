<?php
return [
    'machine_name' => 'RouteManager',
    'name' => 'Route Manager',
    'description' => 'Управление маршрутами CMS из базы данных',
    'version' => '1.1.0',
    'is_system' => 1,
    'dependencies' => ['Kernel' => '1.2.0', 'ModuleManager' => '1.3.0'],
    'install_sql' => ['Database/install.sql'],
    'update_sql' => ['1.1.0' => 'Database/Updates/1.1.0.sql'],
    'routes' => [
        ['method' => 'GET', 'path' => '/admin/routes', 'controller' => '\\Modules\\RouteManager\\Controllers\\RouteManagerController', 'action' => 'index', 'is_admin' => 1],
        ['method' => 'GET', 'path' => '/admin/routes/create', 'controller' => '\\Modules\\RouteManager\\Controllers\\RouteManagerController', 'action' => 'create', 'is_admin' => 1],
        ['method' => 'GET', 'path' => '/admin/routes/edit/(:num)', 'controller' => '\\Modules\\RouteManager\\Controllers\\RouteManagerController', 'action' => 'edit/$1', 'is_admin' => 1],
        ['method' => 'POST', 'path' => '/admin/routes/save', 'controller' => '\\Modules\\RouteManager\\Controllers\\RouteManagerController', 'action' => 'save', 'is_admin' => 1],
        ['method' => 'POST', 'path' => '/admin/routes/save/(:num)', 'controller' => '\\Modules\\RouteManager\\Controllers\\RouteManagerController', 'action' => 'save/$1', 'is_admin' => 1],
        ['method' => 'POST', 'path' => '/admin/routes/sync', 'controller' => '\\Modules\\RouteManager\\Controllers\\RouteManagerController', 'action' => 'sync', 'is_admin' => 1],
        ['method' => 'POST', 'path' => '/admin/routes/toggle/(:num)', 'controller' => '\\Modules\\RouteManager\\Controllers\\RouteManagerController', 'action' => 'toggle/$1', 'is_admin' => 1],
        ['method' => 'POST', 'path' => '/admin/routes/delete/(:num)', 'controller' => '\\Modules\\RouteManager\\Controllers\\RouteManagerController', 'action' => 'delete/$1', 'is_admin' => 1],
    ],
    'menu' => [
        ['title' => 'Маршруты', 'url' => '/admin/routes', 'icon' => 'signpost', 'parent_key' => 'system', 'parent_title' => 'Система', 'parent_weight' => 20, 'weight' => 32, 'is_system' => 1],
    ],
];
