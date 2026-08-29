<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Services;

use App\Modules\Dashboard\Entities\SconcurConsumersObject;
use App\Modules\Dashboard\Entities\SconcurGroupObject;
use App\Modules\Dashboard\Entities\SconcurStatObject;
use App\Modules\Dashboard\Entities\SconcurWorkerObject;
use GuzzleHttp\Client;
use Throwable;

readonly class SconcurStatClient
{
    public function __construct(
        private Client $client = new Client(),
    ) {
    }

    /**
     * Fetch the aggregated SConcur master pool stats from its telemetry panel.
     * Returns an "unavailable" object when the panel is not configured or not
     * reachable (master down / telemetry off).
     */
    public function find(): SconcurStatObject
    {
        $url   = (string) config('sconcur.panel_host');
        $token = (string) config('sconcur.http_server.adminToken');

        if ($url === '' || $token === '') {
            return $this->unavailable();
        }

        try {
            $response = $this->client->get($url, [
                'headers'         => [
                    'Authorization' => 'Bearer ' . $token,
                    // The panel content-negotiates: without this it answers in the
                    // Prometheus text format, not JSON.
                    'Accept'        => 'application/json',
                ],
                'timeout'         => 2,
                'connect_timeout' => 1,
                'http_errors'     => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return $this->unavailable();
            }

            $data = json_decode((string) $response->getBody(), true);

            if (!is_array($data)) {
                return $this->unavailable();
            }

            return $this->map($data);
        } catch (Throwable) {
            return $this->unavailable();
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function map(array $data): SconcurStatObject
    {
        $totals   = (array) ($data['totals'] ?? []);
        $requests = (array) ($totals['requests'] ?? []);
        $master   = (array) ($data['master'] ?? []);

        return new SconcurStatObject(
            available: true,
            name: (string) ($data['name'] ?? ''),
            workersTotal: (int) ($data['workersTotal'] ?? 0),
            workersHung: (int) ($data['workersHung'] ?? 0),
            cpuPercent: (float) ($totals['cpuPercent'] ?? 0),
            memoryRssBytes: (int) ($totals['memory']['rssBytes'] ?? 0),
            goroutines: (int) ($totals['goroutines'] ?? 0),
            requestsCompleted: (int) ($requests['completed'] ?? 0),
            requestsAvgMs: (float) ($requests['avgMs'] ?? 0),
            requestsInFlight: (int) ($requests['inFlight'] ?? 0),
            masterCpuPercent: (float) ($master['cpuPercent'] ?? 0),
            masterMemoryRssBytes: (int) ($master['memory']['rssBytes'] ?? 0),
            groups: array_map($this->mapGroup(...), array_values((array) ($data['groups'] ?? []))),
            workers: array_map($this->mapWorker(...), array_values((array) ($data['workers'] ?? []))),
            consumers: $this->mapConsumers($totals['consumers'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $group
     */
    private function mapGroup(array $group): SconcurGroupObject
    {
        $totals   = (array) ($group['totals'] ?? []);
        $requests = (array) ($totals['requests'] ?? []);

        return new SconcurGroupObject(
            name: (string) ($group['name'] ?? ''),
            workersTotal: (int) ($group['workersTotal'] ?? 0),
            workersHung: (int) ($group['workersHung'] ?? 0),
            cpuPercent: (float) ($totals['cpuPercent'] ?? 0),
            memoryRssBytes: (int) ($totals['memory']['rssBytes'] ?? 0),
            goroutines: (int) ($totals['goroutines'] ?? 0),
            requestsCompleted: (int) ($requests['completed'] ?? 0),
            requestsAvgMs: (float) ($requests['avgMs'] ?? 0),
            requestsInFlight: (int) ($requests['inFlight'] ?? 0),
            consumers: $this->mapConsumers($totals['consumers'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $worker
     */
    private function mapWorker(array $worker): SconcurWorkerObject
    {
        $requests = (array) ($worker['requests'] ?? []);

        return new SconcurWorkerObject(
            pid: (int) ($worker['pid'] ?? 0),
            group: (string) ($worker['group'] ?? ''),
            hung: (bool) ($worker['hung'] ?? false),
            uptimeSeconds: (float) ($worker['uptimeSeconds'] ?? 0),
            cpuPercent: (float) ($worker['cpuPercent'] ?? 0),
            memoryRssBytes: (int) ($worker['memory']['rssBytes'] ?? 0),
            goroutines: (int) ($worker['goroutines'] ?? 0),
            requestsInFlight: (int) ($requests['inFlight'] ?? 0),
            requestsCompleted: (int) ($requests['completed'] ?? 0),
            requestsAvgMs: (float) ($requests['avgMs'] ?? 0),
            consumers: $this->mapConsumers($worker['consumers'] ?? null),
        );
    }

    /**
     * Only a pool consuming a queue reports this section; an HTTP pool omits it.
     */
    private function mapConsumers(mixed $consumers): ?SconcurConsumersObject
    {
        if (!is_array($consumers)) {
            return null;
        }

        return new SconcurConsumersObject(
            coroutines: (int) ($consumers['coroutines'] ?? 0),
            delivered: (int) ($consumers['delivered'] ?? 0),
            acked: (int) ($consumers['acked'] ?? 0),
            refused: (int) ($consumers['refused'] ?? 0),
            timed: (int) ($consumers['timed'] ?? 0),
            avgMs: (float) ($consumers['avgMs'] ?? 0),
            inFlight: (int) ($consumers['inFlight'] ?? 0),
            inFlight1to5s: (int) ($consumers['inFlight1to5s'] ?? 0),
            inFlight5to15s: (int) ($consumers['inFlight5to15s'] ?? 0),
            inFlightOver15s: (int) ($consumers['inFlightOver15s'] ?? 0),
        );
    }

    private function unavailable(): SconcurStatObject
    {
        return new SconcurStatObject(
            available: false,
            name: '',
            workersTotal: 0,
            workersHung: 0,
            cpuPercent: 0.0,
            memoryRssBytes: 0,
            goroutines: 0,
            requestsCompleted: 0,
            requestsAvgMs: 0.0,
            requestsInFlight: 0,
            masterCpuPercent: 0.0,
            masterMemoryRssBytes: 0,
            groups: [],
            workers: [],
            consumers: null,
        );
    }
}
