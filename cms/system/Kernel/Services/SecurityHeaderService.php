<?php

namespace Modules\Kernel\Services;

final class SecurityHeaderService
{
    public function apply(bool $allowDatabaseSettings = true): void
    {
        $response = service('response');
        $settings = $this->settings($allowDatabaseSettings);

        $this->set($response, 'X-Content-Type-Options', 'nosniff');
        $this->set($response, 'X-Permitted-Cross-Domain-Policies', 'none');
        $this->set($response, 'Origin-Agent-Cluster', '?1');
        $this->set($response, 'Cross-Origin-Opener-Policy', 'same-origin');
        $this->set($response, 'Cross-Origin-Resource-Policy', 'same-origin');
        $this->set($response, 'X-Frame-Options', $this->safeHeader($settings['security_frame_options'] ?? 'SAMEORIGIN'));
        $this->set($response, 'Referrer-Policy', $this->safeHeader($settings['security_referrer_policy'] ?? 'strict-origin-when-cross-origin'));
        $this->set($response, 'Permissions-Policy', $this->safeHeader($settings['security_permissions_policy'] ?? 'camera=(), microphone=(), geolocation=()'));

        $csp = $this->safeHeader($settings['security_csp'] ?? "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
        $reportOnly = filter_var($settings['security_csp_report_only'] ?? false, FILTER_VALIDATE_BOOL);
        $this->set($response, $reportOnly ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy', $csp);

        $hstsEnabled = filter_var($settings['security_hsts_enabled'] ?? env('CMS_FORCE_HTTPS', false), FILTER_VALIDATE_BOOL);
        if ($hstsEnabled && (new TrustedProxyService())->isHttps()) {
            $maxAge = max(300, min(63072000, (int) ($settings['security_hsts_max_age'] ?? 31536000)));
            $hsts = 'max-age=' . $maxAge;
            if (filter_var($settings['security_hsts_include_subdomains'] ?? true, FILTER_VALIDATE_BOOL)) {
                $hsts .= '; includeSubDomains';
            }
            if (filter_var($settings['security_hsts_preload'] ?? false, FILTER_VALIDATE_BOOL)) {
                $hsts .= '; preload';
            }
            $this->set($response, 'Strict-Transport-Security', $hsts);
        }
    }

    private function set(object $response, string $name, string $value): void
    {
        $response->setHeader($name, $value);
        if (PHP_SAPI !== 'cli' && ! headers_sent()) {
            header($name . ': ' . $value, true);
        }
    }

    private function settings(bool $allowDatabaseSettings = true): array
    {
        $defaults = [
            'security_csp' => env('CMS_SECURITY_CSP'),
            'security_csp_report_only' => env('CMS_SECURITY_CSP_REPORT_ONLY', false),
            'security_hsts_enabled' => env('CMS_FORCE_HTTPS', false),
            'security_hsts_max_age' => env('CMS_HSTS_MAX_AGE', 31536000),
            'security_hsts_include_subdomains' => env('CMS_HSTS_INCLUDE_SUBDOMAINS', true),
            'security_hsts_preload' => env('CMS_HSTS_PRELOAD', false),
            'security_frame_options' => env('CMS_FRAME_OPTIONS', 'SAMEORIGIN'),
            'security_referrer_policy' => env('CMS_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
            'security_permissions_policy' => env('CMS_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=()'),
        ];

        if (! $allowDatabaseSettings || ! class_exists('Modules\\Settings\\Services\\SettingService')) {
            return $defaults;
        }
        try {
            $service = new \Modules\Settings\Services\SettingService();
            foreach (array_keys($defaults) as $key) {
                $defaults[$key] = $service->get($key, $defaults[$key]);
            }
        } catch (\Throwable) {
        }
        return $defaults;
    }

    private function safeHeader(mixed $value): string
    {
        $value = trim((string) $value);
        return str_replace(["\r", "\n", "\0"], '', $value);
    }
}
