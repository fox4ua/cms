<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\IpRuleModel;
use Modules\Auth\Models\UserSecurityFlagModel;
use Modules\Kernel\Services\AdminActionLoggerService;

class IpAccessService
{
    public function checkLoginIp(?string $userId = null): array
    {
        $ip = (new \Modules\Kernel\Services\ClientIpResolver())->ip();
        $settings = new SecuritySettingsService();

        foreach ((new IpRuleModel())->where('is_active', 1)->findAll() as $rule) {
            if ($this->matches($ip, $rule['ip_value'])) {
                if ($rule['rule_type'] === 'deny') {
                    (new AdminActionLoggerService())->suspicious('ip_denied_global', ['ip' => $ip, 'rule' => $rule['ip_value']]);
                    return ['allowed' => false, 'message' => 'Доступ с этого IP запрещён'];
                }
            }
        }

        if ($settings->bool('admin_ip_allowlist_enabled')) {
            $allowed = false;
            foreach ((new IpRuleModel())->where('is_active', 1)->where('rule_type', 'allow')->findAll() as $rule) {
                if ($this->matches($ip, $rule['ip_value'])) { $allowed = true; break; }
            }
            if (! $allowed) {
                (new AdminActionLoggerService())->suspicious('ip_not_in_admin_allowlist', ['ip' => $ip]);
                return ['allowed' => false, 'message' => 'IP не входит в белый список админки'];
            }
        }

        if ($userId) {
            $flags = (new UserSecurityFlagModel())->find($userId);
            if ($flags) {
                foreach ($this->lines((string) ($flags['denied_ip_list'] ?? '')) as $rule) {
                    if ($this->matches($ip, $rule)) return ['allowed' => false, 'message' => 'IP запрещён для этого пользователя'];
                }
                $allowLines = $this->lines((string) ($flags['allowed_ip_list'] ?? ''));
                if ($allowLines) {
                    $ok = false;
                    foreach ($allowLines as $rule) { if ($this->matches($ip, $rule)) { $ok = true; break; } }
                    if (! $ok) return ['allowed' => false, 'message' => 'IP не разрешён для этого пользователя'];
                }
            }
        }

        return ['allowed' => true, 'message' => ''];
    }

    public function isInternalIp(?string $ip = null): bool
    {
        $ip = $ip ?: (new \Modules\Kernel\Services\ClientIpResolver())->ip();
        $settings = new SecuritySettingsService();
        foreach ($this->lines((string) $settings->get('internal_ip_ranges')) as $range) {
            if ($this->matches($ip, $range)) return true;
        }
        return false;
    }

    public function matches(string $ip, string $rule): bool
    {
        $rule = trim($rule);
        if ($rule === '') return false;
        if ($ip === $rule) return true;
        if (! str_contains($rule, '/')) return false;
        [$subnet, $bits] = explode('/', $rule, 2);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $mask = -1 << (32 - (int) $bits);
            return (ip2long($ip) & $mask) === (ip2long($subnet) & $mask);
        }
        return false;
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
    }
}
