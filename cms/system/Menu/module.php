<?php

return [
    'machine_name' => 'Menu',
    'name' => 'Menu',
    'description' => 'Универсальное управление меню CMS: админские, пользовательские и фронтенд-меню.',
    'version' => '1.2.0',
    'core_version' => '1.0.0',
    'is_system' => 1,
    'dependencies' => ['Kernel' => '1.2.0'],
    'routes' => [
        ['method' => 'GET', 'path' => '/admin/menu', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'index', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'GET', 'path' => '/admin/menu/create', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'createMenu', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'GET', 'path' => '/admin/menu/edit/(:num)', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'editMenu/$1', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/menu/save', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'saveMenu', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/menu/save/(:num)', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'saveMenu/$1', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'GET', 'path' => '/admin/menu/items/create', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'createItem', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'GET', 'path' => '/admin/menu/items/edit/(:num)', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'editItem/$1', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/menu/items/save', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'saveItem', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/menu/items/save/(:num)', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'saveItem/$1', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/menu/items/toggle/(:num)', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'toggleItem/$1', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'POST', 'path' => '/admin/menu/items/delete/(:num)', 'controller' => '\\Modules\\Menu\\Controllers\\MenuController', 'action' => 'deleteItem/$1', 'is_admin' => 1, 'is_system' => 1],
    ],
    'menus' => [
        'admin_sidebar' => [
            ['item_key' => 'system', 'title' => 'Система', 'link_type' => 'heading', 'url' => '#', 'icon' => 'boxes', 'weight' => 20, 'is_system' => 1],
            ['item_key' => 'menu.manage', 'parent_key' => 'system', 'title' => 'Меню', 'url' => '/admin/menu', 'icon' => 'list', 'weight' => 30, 'is_system' => 1],
        ],
    ],
    'install_sql' => [],
    'update_sql' => ['1.1.0' => 'Database/Updates/1.1.0.sql', '1.2.0' => 'Database/Updates/1.2.0.sql'],
];
