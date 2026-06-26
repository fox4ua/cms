<?php

namespace Modules\Kernel\Services;

final class HostGuardService
{
    public function assertAllowed(): void
    {
        $allowed = array_filter(array_map('trim', explode(',', (string) env('CMS_ALLOWED_HOSTS', ''))));
        if (! $allowed) {
            return;
        }
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host) ?: $host;
        if (! in_array($host, array_map('strtolower', $allowed), true)) {
            http_response_code(400);
            echo 'Bad request.';
            exit;
        }
    }
}
