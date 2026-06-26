<?php

namespace Modules\Auth\Controllers;

use Modules\Kernel\Controllers\AdminController;
use Modules\Auth\Models\IpRuleModel;
use Modules\Kernel\Services\AdminActionLoggerService;

class IpRulesController extends AdminController
{
    public function index()
    {
        return $this->render('Modules\Auth\Views\ip_rules', [
            'pageTitle' => 'IP правила авторизации',
            'rules' => (new IpRuleModel())->orderBy('id', 'DESC')->findAll(),
        ]);
    }

    public function add()
    {
        $ip = trim((string) $this->request->getPost('ip_value'));
        $type = (string) $this->request->getPost('rule_type');
        if ($ip === '' || ! in_array($type, ['allow', 'deny'], true) || ! $this->isValidIpOrCidr($ip)) {
            return redirect()->back()->with('error', 'Некорректное IP/CIDR правило');
        }
        (new IpRuleModel())->insert([
            'ip_value' => $ip,
            'rule_type' => $type,
            'description' => trim((string) $this->request->getPost('description')),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        (new AdminActionLoggerService())->log('auth.ip_rule.add', 'ip_rule', $ip);
        return redirect()->to('/admin/auth/ip-rules')->with('success', 'Правило добавлено');
    }

    private function isValidIpOrCidr(string $value): bool
    {
        if (str_contains($value, '/')) {
            [$ip, $mask] = explode('/', $value, 2);
            if (! ctype_digit($mask)) { return false; }
            $max = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 128 : 32;
            return filter_var($ip, FILTER_VALIDATE_IP) !== false && (int) $mask >= 0 && (int) $mask <= $max;
        }
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    public function delete($id)
    {
        (new IpRuleModel())->delete((int) $id);
        (new AdminActionLoggerService())->log('auth.ip_rule.delete', 'ip_rule', (string) $id);
        return redirect()->to('/admin/auth/ip-rules')->with('success', 'Правило удалено');
    }
}
