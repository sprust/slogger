<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Services;

use App\Modules\Dashboard\Entities\SconcurConsumersObject;
use App\Modules\Dashboard\Entities\SconcurGroupObject;
use App\Modules\Dashboard\Entities\SconcurRequestsObject;
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
        $token = (string) config('sconcur.master.adminToken');

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
        $totals = (array) ($data['totals'] ?? []);
        $master = (array) ($data['master'] ?? []);

        return new SconcurStatObject(
            available: true,
            name: (string) ($data['name'] ?? ''),
            workersTotal: (int) ($data['workersTotal'] ?? 0),
            workersHung: (int) ($data['workersHung'] ?? 0),
            cpuPercent: (float) ($totals['cpuPercent'] ?? 0),
            memoryRssBytes: (int) ($totals['memory']['rssBytes'] ?? 0),
            goroutines: (int) ($totals['goroutines'] ?? 0),
            requests: $this->mapRequests($totals['requests'] ?? null),
            masterCpuPercent: (float) ($master['cpuPercent'] ?? 0),
            masterMemoryRssBytes: (int) ($master['memory']['rssBytes'] ?? 0),
            groups: $this->withSilentGroups(
                array_map($this->mapGroup(...), array_values((array) ($data['groups'] ?? []))),
            ),
            workers: array_map($this->mapWorker(...), array_values((array) ($data['workers'] ?? []))),
            consumers: $this->mapConsumers($totals['consumers'] ?? null),
        );
    }

    /**
     * Adds the configured groups the panel says nothing about, with their numbers at
     * zero.
     *
     * The panel only knows a worker that pushes telemetry to it, and pushing is done by
     * the Go side of the server and consumer runtimes. A group of plain artisan workers
     * — the periodic task pool — runs neither, so it is configured and supervised and
     * simply absent from these numbers. Dropping it from the dashboard would read as
     * "there is no such pool"; zeros read as "no measurements", which is the truth.
     *
     * @param list<SconcurGroupObject> $groups
     *
     * @return list<SconcurGroupObject>
     */
    private function withSilentGroups(array $groups): array
    {
        $reported = array_map(static fn(SconcurGroupObject $group): string => $group->name, $groups);

        foreach ((array) config('sconcur.master.groups', []) as $group) {
            $name = is_array($group) ? (string) ($group['name'] ?? '') : '';

            if ($name === '' || in_array($name, $reported, true)) {
                continue;
            }

            $groups[] = new SconcurGroupObject(
                name: $name,
                workersTotal: 0,
                workersHung: 0,
                cpuPercent: 0,
                memoryRssBytes: 0,
                goroutines: 0,
                requests: null,
                consumers: null,
            );
        }

        return $groups;
    }

    /**
     * @param array<string, mixed> $group
     */
    private function mapGroup(array $group): SconcurGroupObject
    {
        $totals = (array) ($group['totals'] ?? []);

        return new SconcurGroupObject(
            name: (string) ($group['name'] ?? ''),
            workersTotal: (int) ($group['workersTotal'] ?? 0),
            workersHung: (int) ($group['workersHung'] ?? 0),
            cpuPercent: (float) ($totals['cpuPercent'] ?? 0),
            memoryRssBytes: (int) ($totals['memory']['rssBytes'] ?? 0),
            goroutines: (int) ($totals['goroutines'] ?? 0),
            requests: $this->mapRequests($totals['requests'] ?? null),
            consumers: $this->mapConsumers($totals['consumers'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $worker
     */
    private function mapWorker(array $worker): SconcurWorkerObject
    {
        return new SconcurWorkerObject(
            pid: (int) ($worker['pid'] ?? 0),
            group: (string) ($worker['group'] ?? ''),
            hung: (bool) ($worker['hung'] ?? false),
            uptimeSeconds: (float) ($worker['uptimeSeconds'] ?? 0),
            cpuPercent: (float) ($worker['cpuPercent'] ?? 0),
            memoryRssBytes: (int) ($worker['memory']['rssBytes'] ?? 0),
            goroutines: (int) ($worker['goroutines'] ?? 0),
            requests: $this->mapRequests($worker['requests'] ?? null),
            consumers: $this->mapConsumers($worker['consumers'] ?? null),
        );
    }

    /**
     * Only a pool serving requests reports this section; a consumer pool omits it, and
     * the omission is kept as null rather than flattened into zeroes — a counter that
     * does not exist reads differently from one that stands at nothing.
     */
    private function mapRequests(mixed $requests): ?SconcurRequestsObject
    {
        if (!is_array($requests)) {
            return null;
        }

        return new SconcurRequestsObject(
            completed: (int) ($requests['completed'] ?? 0),
            avgMs: (float) ($requests['avgMs'] ?? 0),
            inFlight: (int) ($requests['inFlight'] ?? 0),
            inFlight1to5s: (int) ($requests['inFlight1to5s'] ?? 0),
            inFlight5to15s: (int) ($requests['inFlight5to15s'] ?? 0),
            inFlightOver15s: (int) ($requests['inFlightOver15s'] ?? 0),
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
            requests: null,
            masterCpuPercent: 0.0,
            masterMemoryRssBytes: 0,
            groups: [],
            workers: [],
            consumers: null,
        );
    }
}
