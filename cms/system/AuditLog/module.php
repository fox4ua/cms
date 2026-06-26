<?php
return [
    'machine_name' => 'AuditLog',
    'name' => 'Audit Log',
    'description' => 'Просмотр журнала действий и подозрительных событий',
    'version' => '1.0.0',
    'is_system' => 1,
    'dependencies' => ['Kernel' => '1.2.0'],
    'maintenance_providers' => ['\\Modules\\AuditLog\\Services\\AuditLogCleanupProvider'],
    'routes' => [
        ['method' => 'GET', 'path' => '/admin/security/audit', 'controller' => '\\Modules\\AuditLog\\Controllers\\AuditLogController', 'action' => 'audit', 'is_admin' => 1, 'is_system' => 1],
        ['method' => 'GET', 'path' => '/admin/security/suspicious', 'controller' => '\\Modules\\AuditLog\\Controllers\\AuditLogController', 'action' => 'suspicious', 'is_admin' => 1, 'is_system' => 1],
    ],
    'menu' => [
        ['title' => 'Audit log', 'url' => '/admin/security/audit', 'icon' => 'list', 'parent_key' => 'security', 'parent_title' => 'Безопасность', 'parent_weight' => 30, 'weight' => 80, 'is_system' => 1],
        ['title' => 'Suspicious logs', 'url' => '/admin/security/suspicious', 'icon' => 'warning', 'parent_key' => 'security', 'parent_title' => 'Безопасность', 'parent_weight' => 30, 'weight' => 81, 'is_system' => 1],
    ],
];
