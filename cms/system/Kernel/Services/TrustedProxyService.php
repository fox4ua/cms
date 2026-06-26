<?php

namespace Modules\Kernel\Services;

final class TrustedProxyService
{
    private array $trustedProxies;
    private array $trustedHeaders;

    public function __construct(?array $trustedProxies = null, ?array $trustedHeaders = null)
    {
        $this->trustedProxies = $trustedProxies ?? $this->csv((string) env('CMS_TRUSTED_PROXIES', ''));
        $this->trustedHeaders = array_map('strtolower', $trustedHeaders ?? $this->csv((string) env(
            'CMS_TRUSTED_PROXY_HEADERS',
            'forwarded,x-forwarded-for,x-real-ip,cf-connecting-ip,x-forwarded-proto'
        )));
    }

    public function clientIp(): string
    {
        $remote = $this->normalizeIp((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote === '' || ! $this->isTrustedProxy($remote)) {
            return $remote !== '' ? $remote : '0.0.0.0';
        }

        foreach (['forwarded', 'cf-connecting-ip', 'x-forwarded-for', 'x-real-ip'] as $header) {
            if (! $this->trustsHeader($header)) {
                continue;
            }
            $ips = $this->headerIps($header);
            if ($ips === []) {
                continue;
            }

            // Walk from the proxy nearest to the application towards the client.
            $chain = array_merge($ips, [$remote]);
            for ($i = count($chain) - 1; $i >= 0; $i--) {
                $candidate = $this->normalizeIp($chain[$i]);
                if ($candidate === '') {
                    continue;
                }
                if (! $this->isTrustedProxy($candidate)) {
                    return $candidate;
                }
            }

            return $this->normalizeIp($ips[0]) ?: $remote;
        }

        return $remote;
    }

    public function isHttps(): bool
    {
        if (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        $remote = $this->normalizeIp((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote === '' || ! $this->isTrustedProxy($remote) || ! $this->trustsHeader('x-forwarded-proto')) {
            return false;
        }

        $proto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
        return $proto === 'https';
    }

    public function isTrustedProxy(string $ip): bool
    {
        $ip = $this->normalizeIp($ip);
        if ($ip === '') {
            return false;
        }
        foreach ($this->trustedProxies as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    public function configured(): bool
    {
        return $this->trustedProxies !== [];
    }

    private function headerIps(string $header): array
    {
        $value = match ($header) {
            'forwarded' => (string) ($_SERVER['HTTP_FORWARDED'] ?? ''),
            'cf-connecting-ip' => (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
            'x-forwarded-for' => (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
            'x-real-ip' => (string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''),
            default => '',
        };

        if ($value === '') {
            return [];
        }

        if ($header === 'forwarded') {
            $ips = [];
            foreach (explode(',', $value) as $part) {
                if (preg_match('/(?:^|;)\s*for=(?:"?\[?)([^;\]",]+)(?:\]?"?)/i', $part, $m)) {
                    $candidate = preg_replace('/:\d+$/', '', trim($m[1]));
                    if ($this->normalizeIp((string) $candidate) !== '') {
                        $ips[] = (string) $candidate;
                    }
                }
            }
            return $ips;
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $ip): bool => $this->normalizeIp($ip) !== ''));
    }

    private function trustsHeader(string $header): bool
    {
        return in_array(strtolower($header), $this->trustedHeaders, true);
    }

    private function normalizeIp(string $ip): string
    {
        $ip = trim($ip, " \t\n\r\0\x0B[]\"");
        if (str_contains($ip, '%')) {
            $ip = explode('%', $ip, 2)[0];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    private function ipInRange(string $ip, string $range): bool
    {
        $range = trim($range);
        if ($range === '') {
            return false;
        }
        if (! str_contains($range, '/')) {
            return hash_equals($this->normalizeIp($range), $ip);
        }

        [$network, $prefix] = explode('/', $range, 2);
        $networkBinary = @inet_pton($this->normalizeIp($network));
        $ipBinary = @inet_pton($ip);
        if ($networkBinary === false || $ipBinary === false || strlen($networkBinary) !== strlen($ipBinary)) {
            return false;
        }

        $bits = strlen($networkBinary) * 8;
        $prefixLength = filter_var($prefix, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => $bits]]);
        if ($prefixLength === false) {
            return false;
        }

        $bytes = intdiv((int) $prefixLength, 8);
        $remainder = (int) $prefixLength % 8;
        if ($bytes > 0 && substr($networkBinary, 0, $bytes) !== substr($ipBinary, 0, $bytes)) {
            return false;
        }
        if ($remainder === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainder)) & 0xFF;
        return (ord($networkBinary[$bytes]) & $mask) === (ord($ipBinary[$bytes]) & $mask);
    }

    private function csv(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== ''));
    }
}
