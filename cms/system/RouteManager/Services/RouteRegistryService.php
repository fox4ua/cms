<?php
namespace Modules\RouteManager\Services;

use Modules\Kernel\Services\CmsCacheService;
use Modules\ModuleManager\Models\CmsModuleModel;
use Modules\RouteManager\Models\CmsRouteModel;
use Modules\Kernel\Services\ModulePathService;
use Throwable;

class RouteRegistryService
{
    public function register($routes): void
    {
        try {
            $cache = cache();
            $rows = $cache->get('cms_routes_registry');
            if (! is_array($rows)) {
                $db = db_connect();
                if (! $db->tableExists('cms_routes')) return;
                $rows = $db->table('cms_routes r')
                    ->select('r.*')
                    ->join('cms_modules m', 'm.machine_name = r.module', 'left')
                    ->where('r.is_active', 1)
                    ->groupStart()->where('m.is_enabled', 1)->orWhere('r.is_system', 1)->groupEnd()
                    ->orderBy('r.sort_order', 'ASC')->get()->getResultArray();
                $cache->save('cms_routes_registry', $rows, max(30, (int) env('CMS_ROUTE_CACHE_TTL', 300)));
            }
            foreach ($rows as $row) {
                if (! $this->isRouteRowSafe($row)) continue;
                $method = strtolower((string) $row['http_method']);
                $target = ltrim((string) $row['controller'], '\\') . '::' . (string) $row['action'];
                if ($method === 'any') $routes->add($row['path'], '\\' . $target);
                elseif ($method === 'match') $routes->match(['get','post'], $row['path'], '\\' . $target);
                elseif (method_exists($routes, $method)) $routes->{$method}($row['path'], '\\' . $target);
            }
        } catch (Throwable) { return; }
    }

    public function syncAllInstalled(): int
    {
        $count = 0;
        foreach ((new CmsModuleModel())->where('is_installed', 1)->findAll() as $module) {
            $metaFile = (new ModulePathService())->manifest((string) $module['machine_name']);
            if ($metaFile === null || ! is_file($metaFile)) continue;
            $meta = include $metaFile;
            if (is_array($meta)) $count += $this->syncModuleRoutes($module['machine_name'], $meta, (int)$module['is_enabled'] === 1);
        }
        (new CmsCacheService())->clearKernel();
        return $count;
    }

    public function syncModuleRoutes(string $module, array $meta, bool $active = true): int
    {
        $model = new CmsRouteModel();
        $validator = new RouteValidationService();
        $count = 0;
        foreach ((array)($meta['routes'] ?? []) as $i => $route) {
            if (! is_array($route)) continue;
            $method = strtoupper((string)($route['method'] ?? 'GET'));
            $path = (string)($route['path'] ?? '');
            $data = [
                'module' => $module,
                'route_key' => (string)($route['key'] ?? ($module . ':' . $method . ':' . $path)),
                'http_method' => $method,
                'path' => $path,
                'controller' => (string)($route['controller'] ?? ''),
                'action' => (string)($route['action'] ?? 'index'),
                'is_admin' => (int)($route['is_admin'] ?? str_starts_with($path, '/admin/')),
                'is_active' => $active ? 1 : 0,
                'is_system' => (int)($route['is_system'] ?? ($meta['is_system'] ?? 0)),
                'sort_order' => (int)($route['sort_order'] ?? (100 + $i)),
            ];
            $exists = $model->where('route_key', $data['route_key'])->first();
            $normalized = $validator->normalize($data);
            $errors = $validator->validate($normalized, $exists['id'] ?? null);
            if ($errors) continue;
            if ($exists) $model->update($exists['id'], $normalized);
            else { $normalized['created_at'] = date('Y-m-d H:i:s'); $model->insert($normalized); }
            $count++;
        }
        return $count;
    }

    public function setModuleRoutesActive(string $module, bool $active): void
    {
        (new CmsRouteModel())->where('module', $module)->where('is_system', 0)->set(['is_active' => $active ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')])->update();
        (new CmsCacheService())->clearKernel();
    }

    public function hasRouteConflict(string $method, string $path, string $module): ?array
    {
        $row = (new CmsRouteModel())->where('http_method', strtoupper($method))->where('path', $path)->where('module !=', $module)->first();
        return $row ?: null;
    }

    private function isRouteRowSafe(array $row): bool
    {
        return in_array(strtoupper((string)$row['http_method']), RouteValidationService::METHODS, true)
            && preg_match('/^\/[A-Za-z0-9_\/().:-]*$/', (string)$row['path'])
            && preg_match('/^\\\\?Modules\\\\[A-Za-z0-9_]+\\\\Controllers\\\\[A-Za-z0-9_\\\\]+$/', (string)$row['controller'])
            && preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\/[0-9$]+)?$/', (string)$row['action']);
    }
}
