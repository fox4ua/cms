<?php

namespace Modules\SystemHealth\Controllers;

use CodeIgniter\Controller;
use Modules\SystemHealth\Services\ReadinessService;

final class ProbeController extends Controller
{
    public function health()
    {
        return $this->probeResponse(200, [
            'status' => 'alive',
            'timestamp' => gmdate(DATE_ATOM),
        ]);
    }

    public function ready()
    {
        $status = (new ReadinessService())->status();
        $payload = [
            'status' => $status['ready'] ? 'ready' : 'not_ready',
            'timestamp' => $status['timestamp'],
        ];

        if ($this->canShowDetails()) {
            $payload['checks'] = $status['checks'];
        }

        return $this->probeResponse($status['ready'] ? 200 : 503, $payload);
    }

    private function canShowDetails(): bool
    {
        $expected = trim((string) env('CMS_PROBE_TOKEN', ''));
        if ($expected === '') {
            return false;
        }

        $provided = trim((string) $this->request->getHeaderLine('X-CMS-Probe-Token'));
        return $provided !== '' && hash_equals($expected, $provided);
    }

    private function probeResponse(int $status, array $payload)
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setJSON($payload);
    }
}
