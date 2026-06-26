<?php
namespace Modules\RouteManager\Services;

use Modules\RouteManager\Models\CmsRouteModel;

class RouteValidationService
{
    public const METHODS = ['GET','POST','PUT','PATCH','DELETE','MATCH','ANY'];

    public function validate(array $data, ?int $ignoreId = null): array
    {
        $errors = [];
        $method = strtoupper(trim((string)($data['http_method'] ?? 'GET')));
        $path = trim((string)($data['path'] ?? ''));
        $controller = trim((string)($data['controller'] ?? ''));
        $action = trim((string)($data['action'] ?? 'index'));
        $module = trim((string)($data['module'] ?? ''));
        $routeKey = trim((string)($data['route_key'] ?? ''));

        if (! in_array($method, self::METHODS, true)) $errors[] = 'Недопустимый HTTP method';
        if ($module === '' || ! preg_match('/^[A-Za-z][A-Za-z0-9_]{1,99}$/', $module)) $errors[] = 'Некорректный module';
        if ($routeKey !== '' && ! preg_match('/^[A-Za-z0-9_:\/().-]{3,190}$/', $routeKey)) $errors[] = 'Некорректный route_key';
        if ($path === '' || $path[0] !== '/') $errors[] = 'Path должен начинаться с /';
        if (preg_match('/\s/', $path) || str_contains($path, '//')) $errors[] = 'Path не должен содержать пробелы или //';
        if (! preg_match('/^\/?[A-Za-z0-9_\/().:-]+$/', $path)) $errors[] = 'Path содержит запрещённые символы';
        if (! preg_match('/^\\\\?Modules\\\\[A-Za-z0-9_]+\\\\Controllers\\\\[A-Za-z0-9_\\\\]+$/', $controller)) $errors[] = 'Controller должен быть в namespace Modules\\...\\Controllers';
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\/[0-9$]+)?$/', $action)) $errors[] = 'Некорректный action';
        if ($controller && $action && ! str_contains($action, '/')) {
            $class = '\\' . ltrim($controller, '\\');
            if (class_exists($class) && ! method_exists($class, $action)) $errors[] = 'Controller существует, но action не найден';
        }

        $model = new CmsRouteModel();
        $q = $model->where('http_method', $method)->where('path', $path);
        if ($ignoreId) $q->where('id !=', $ignoreId);
        if ($q->first()) $errors[] = 'Конфликт: такой method + path уже существует';

        if ($routeKey !== '') {
            $q = $model->where('route_key', $routeKey);
            if ($ignoreId) $q->where('id !=', $ignoreId);
            if ($q->first()) $errors[] = 'Конфликт: такой route_key уже существует';
        }
        return $errors;
    }

    public function normalize(array $data): array
    {
        $method = strtoupper(trim((string)($data['http_method'] ?? 'GET')));
        $path = trim((string)($data['path'] ?? ''));
        $module = trim((string)($data['module'] ?? ''));
        return [
            'module' => $module,
            'route_key' => trim((string)($data['route_key'] ?? ($module . ':' . $method . ':' . $path))),
            'http_method' => $method,
            'path' => $path,
            'controller' => '\\' . ltrim(trim((string)($data['controller'] ?? '')), '\\'),
            'action' => trim((string)($data['action'] ?? 'index')),
            'is_admin' => ! empty($data['is_admin']) ? 1 : 0,
            'is_active' => ! empty($data['is_active']) ? 1 : 0,
            'is_system' => ! empty($data['is_system']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 100),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
