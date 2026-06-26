<?php
return [
    'machine_name' => 'Dashboard',
    'name' => 'Dashboard',
    'description' => 'Главная панель администрирования и виджеты CMS',
    'version' => '1.0.0',
    'is_system' => 1,
    'dependencies' => ['Kernel' => '1.2.0'],
    'routes' => [
        ['method' => 'GET', 'path' => '/admin', 'controller' => '\\Modules\\Dashboard\\Controllers\\DashboardController', 'action' => 'index', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'GET', 'path' => '/admin/dashboard', 'controller' => '\\Modules\\Dashboard\\Controllers\\DashboardController', 'action' => 'index', 'is_admin' => 1, 'is_system' => 1],
    ],
    'menu' => [
        ['title' => 'Панель', 'url' => '/admin', 'icon' => 'speedometer', 'menu_key' => 'dashboard', 'weight' => 10, 'is_system' => 1],
    ],
];
