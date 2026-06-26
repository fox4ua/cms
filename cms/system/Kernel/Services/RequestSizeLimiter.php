<?php

namespace Modules\Kernel\Services;

final class RequestSizeLimiter
{
    public function assertAllowed(): void
    {
        $limitMb = (int) env('CMS_MAX_REQUEST_MB', 32);
        if ($limitMb <= 0) {
            $limitMb = 32;
        }
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength > $limitMb * 1024 * 1024) {
            http_response_code(413);
            echo 'Request entity too large.';
            exit;
        }
    }
}
