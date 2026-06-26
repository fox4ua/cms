<?php

// Production bootstrap routes. Normal CMS routes are loaded from cms_routes.
$routes->get('/health', '\Modules\SystemHealth\Controllers\ProbeController::health');
$routes->get('/ready', '\Modules\SystemHealth\Controllers\ProbeController::ready');
$routes->get('/service', '\Modules\Kernel\Controllers\ServiceController::index');
$routes->get('/update-required', '\Modules\Kernel\Controllers\ServiceController::updateRequired');
$routes->get('/install', '\Modules\Installer\Controllers\InstallerController::index');
$routes->post('/install', '\Modules\Installer\Controllers\InstallerController::install');

// Liveness/readiness and service pages must remain reachable without loading DB routes.
$bootstrapPath = class_exists('\Modules\Kernel\Services\RequestPathService')
    ? (new \Modules\Kernel\Services\RequestPathService())->path()
    : '/';
$installerBootstrap = $bootstrapPath === '/install'
    && filter_var(env('CMS_INSTALLER_ENABLED', false), FILTER_VALIDATE_BOOL)
    && ! is_file(WRITEPATH . 'cms-installed.lock');
if (in_array($bootstrapPath, ['/health', '/ready', '/service', '/update-required'], true) || $installerBootstrap) {
    if (class_exists('\Modules\Kernel\Services\BootstrapLoader')) {
        \Modules\Kernel\Services\BootstrapLoader::applyRequestGuards(false);
    }
    return;
}

$bootstrap = null;
if (class_exists('\Modules\Kernel\Services\BootstrapLoader')) {
    $bootstrap = \Modules\Kernel\Services\BootstrapLoader::boot();
}

if ($bootstrap !== null && ! $bootstrap->canUseDatabaseRoutes()) {
    if ($bootstrap->installerRequired) {
        $routes->get('/', '\Modules\Installer\Controllers\InstallerController::index');
        $routes->get('/login', '\Modules\Installer\Controllers\InstallerController::index');
        $routes->post('/login', '\Modules\Installer\Controllers\InstallerController::index');
        $routes->get('/admin', '\Modules\Installer\Controllers\InstallerController::index');
        $routes->get('/admin/(:any)', '\Modules\Installer\Controllers\InstallerController::index');
        return;
    }

    if ($bootstrap->updateRequired) {
        $routes->get('/', '\Modules\Kernel\Controllers\ServiceController::updateRequired');
        $routes->get('/login', '\Modules\Kernel\Controllers\ServiceController::updateRequired');
        $routes->post('/login', '\Modules\Kernel\Controllers\ServiceController::updateRequired');
        $routes->get('/admin', '\Modules\Kernel\Controllers\ServiceController::updateRequired');
        $routes->get('/admin/(:any)', '\Modules\Kernel\Controllers\ServiceController::updateRequired');
        return;
    }

    $code = $bootstrap->errorCode ?: ($bootstrap->maintenance ? 'MAINT-001' : 'SERVICE');
    $routes->get('/', '\Modules\Kernel\Controllers\ServiceController::index/' . $code);
    $routes->get('/login', '\Modules\Kernel\Controllers\ServiceController::index/' . $code);
    $routes->post('/login', '\Modules\Kernel\Controllers\ServiceController::index/' . $code);
    $routes->post('/logout', '\Modules\Kernel\Controllers\ServiceController::index/' . $code);
    $routes->get('/admin', '\Modules\Kernel\Controllers\ServiceController::index/' . $code);
    $routes->get('/admin/(:any)', '\Modules\Kernel\Controllers\ServiceController::index/' . $code);
    return;
}

$routes->get('/login', '\Modules\Auth\Controllers\AuthController::login');
$routes->post('/login', '\Modules\Auth\Controllers\AuthController::attemptLogin');
$routes->post('/logout', '\Modules\Auth\Controllers\AuthController::logout');

if (class_exists('\Modules\RouteManager\Services\RouteRegistryService')) {
    (new \Modules\RouteManager\Services\RouteRegistryService())->register($routes);
}
