<?php
namespace Modules\Auth\Services;

use Modules\Auth\Models\AuthExtensionHookModel;
use Modules\Kernel\Services\AdminActionLoggerService;
use Throwable;

class AuthExtensionPipelineService
{
    public const STATUS_ALLOW = 'allow';
    public const STATUS_DENY = 'deny';
    public const STATUS_CHALLENGE_REQUIRED = 'challenge_required';
    public const STATUS_CHALLENGE_PASSED = 'challenge_passed';

    public function run(string $hook, array $context = []): array
    {
        $result = ['status' => self::STATUS_ALLOW, 'allowed' => true, 'message' => null, 'challenge' => null, 'context' => $context];
        if (! AuthHookNames::isAllowed($hook)) {
            return ['status' => self::STATUS_DENY, 'allowed' => false, 'message' => 'Недопустимый auth hook', 'challenge' => null, 'context' => $context];
        }
        try {
            $rows = (new AuthExtensionHookModel())->where('hook_name', $hook)->where('is_active', 1)->orderBy('priority','ASC')->findAll();
        } catch (Throwable) { return $result; }
        foreach ($rows as $row) {
            $class = (string)$row['handler_class']; $method = (string)$row['handler_method'];
            if (! class_exists($class) || ! method_exists($class, $method)) continue;
            try {
                $response = (new $class())->{$method}($result['context']);
                if (! is_array($response)) continue;
                $result['context'] = $response['context'] ?? $result['context'];
                $status = (string)($response['status'] ?? ($response['allowed'] ?? true ? self::STATUS_ALLOW : self::STATUS_DENY));
                if (! in_array($status, [self::STATUS_ALLOW,self::STATUS_DENY,self::STATUS_CHALLENGE_REQUIRED,self::STATUS_CHALLENGE_PASSED], true)) {
                    $status = self::STATUS_DENY;
                }
                if ($status !== self::STATUS_ALLOW && $status !== self::STATUS_CHALLENGE_PASSED) {
                    $result['status'] = $status;
                    $result['allowed'] = false;
                    $result['message'] = $response['message'] ?? ($status === self::STATUS_CHALLENGE_REQUIRED ? 'Требуется дополнительная проверка' : 'Вход остановлен модулем расширения авторизации');
                    $result['challenge'] = $response['challenge'] ?? null;
                    break;
                }
                if ($status === self::STATUS_CHALLENGE_PASSED) $result['status'] = self::STATUS_CHALLENGE_PASSED;
            } catch (Throwable $e) {
                (new AdminActionLoggerService())->suspicious('auth_extension_failed', ['hook' => $hook, 'module' => $row['module'], 'error' => $e->getMessage()]);
                $result['status'] = self::STATUS_DENY;
                $result['allowed'] = false;
                $result['message'] = 'Ошибка расширения авторизации: ' . $row['module'];
                break;
            }
        }
        return $result;
    }
}
