<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Services;

use App\Modules\Dashboard\Entities\SconcurGroupObject;
use App\Modules\Dashboard\Entities\SconcurStatObject;
use App\Modules\Dashboard\Entities\SconcurWorkerObject;
use App\Modules\Dashboard\Entities\SconcurWorkObject;
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

        $groups = $this->orderByConfig(
            $this->withSilentGroups(
                array_map($this->mapGroup(...), array_values((array) ($data['groups'] ?? []))),
            ),
        );

        return new SconcurStatObject(
            available: true,
            name: (string) ($data['name'] ?? ''),
            workersTotal: (int) ($data['workersTotal'] ?? 0),
            workersHung: (int) ($data['workersHung'] ?? 0),
            cpuPercent: (float) ($totals['cpuPercent'] ?? 0),
            memoryRssBytes: (int) ($totals['memory']['rssBytes'] ?? 0),
            runtimeTasks: (int) ($totals['runtimeTasks'] ?? 0),
            work: $this->mapWork($totals['requests'] ?? null, $totals['consumers'] ?? null),
            masterCpuPercent: (float) ($master['cpuPercent'] ?? 0),
            masterMemoryRssBytes: (int) ($master['memory']['rssBytes'] ?? 0),
            groups: $groups,
            workers: $this->orderWorkers(
                array_map($this->mapWorker(...), array_values((array) ($data['workers'] ?? []))),
                $groups,
            ),
        );
    }

    /**
     * Adds the configured groups the panel says nothing about, with their numbers at
     * zero.
     *
     * The panel only knows a worker that pushes telemetry to it, so a configured group is
     * missing from its answer whenever nothing of it is running. Dropping such a group
     * from the dashboard would read as "there is no such pool"; zeros read as "no
     * measurements", which is the truth.
     *
     * @param list<SconcurGroupObject> $groups
     *
     * @return list<SconcurGroupObject>
     */
    private function withSilentGroups(array $groups): array
    {
        $reported = array_map(static fn(SconcurGroupObject $group): string => $group->name, $groups);

        foreach ($this->configuredGroupNames() as $name) {
            if (in_array($name, $reported, true)) {
                continue;
            }

            $groups[] = new SconcurGroupObject(
                name: $name,
                workersTotal: 0,
                workersHung: 0,
                cpuPercent: 0,
                memoryRssBytes: 0,
                runtimeTasks: 0,
                work: null,
            );
        }

        return $groups;
    }

    /**
     * Puts the groups in the order the master config declares them.
     *
     * The panel builds its answer from a map, so the pools arrive in whatever order the
     * runtime iterated them and that order changes between calls. On a page refreshing
     * every second the rows swapped places under the cursor. The config is the one stable
     * order there is, and it is the order the pools are described in.
     *
     * A group the config does not name — one renamed while the master kept running — goes
     * after the configured ones rather than being dropped or sorted into them.
     *
     * @param list<SconcurGroupObject> $groups
     *
     * @return list<SconcurGroupObject>
     */
    private function orderByConfig(array $groups): array
    {
        $order = array_flip($this->configuredGroupNames());

        usort(
            $groups,
            static fn(SconcurGroupObject $a, SconcurGroupObject $b): int => ($order[$a->name] ?? PHP_INT_MAX)
                <=> ($order[$b->name] ?? PHP_INT_MAX),
        );

        return $groups;
    }

    /**
     * Sorts the workers the way the groups above them are sorted, then by pid, so the two
     * tables read in the same order and a worker keeps its row between refreshes.
     *
     * @param list<SconcurWorkerObject> $workers
     * @param list<SconcurGroupObject>  $groups
     *
     * @return list<SconcurWorkerObject>
     */
    private function orderWorkers(array $workers, array $groups): array
    {
        $order = array_flip(array_map(static fn(SconcurGroupObject $group): string => $group->name, $groups));

        usort(
            $workers,
            static fn(SconcurWorkerObject $a, SconcurWorkerObject $b): int => [
                $order[$a->group] ?? PHP_INT_MAX,
                $a->pid,
            ] <=> [
                $order[$b->group] ?? PHP_INT_MAX,
                $b->pid,
            ],
        );

        return $workers;
    }

    /**
     * The group names of the master config, in the order it declares them.
     *
     * @return list<string>
     */
    private function configuredGroupNames(): array
    {
        $names = [];

        foreach ((array) config('sconcur.master.groups', []) as $group) {
            $name = is_array($group) ? (string) ($group['name'] ?? '') : '';

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
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
            runtimeTasks: (int) ($totals['runtimeTasks'] ?? 0),
            work: $this->mapWork($totals['requests'] ?? null, $totals['consumers'] ?? null),
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
            runtimeTasks: (int) ($worker['runtimeTasks'] ?? 0),
            work: $this->mapWork($worker['requests'] ?? null, $worker['consumers'] ?? null),
        );
    }

    /**
     * Folds the panel's two workload sections into the one the dashboard shows. Which of
     * them a pool sends depends on what it runs, and no pool sends both — but the master's
     * totals do, being the sum of unlike pools, so both are read here rather than one or
     * the other. See SconcurWorkObject for why each pair of counters is the same quantity.
     *
     * Null when neither section is there: a pool that counts nothing is not a pool that
     * counted zero.
     */
    private function mapWork(mixed $requests, mixed $consumers): ?SconcurWorkObject
    {
        $requests  = is_array($requests) ? $requests : null;
        $consumers = is_array($consumers) ? $consumers : null;

        if ($requests === null && $consumers === null) {
            return null;
        }

        $completed = (int) ($requests['completed'] ?? 0);
        $acked     = (int) ($consumers['acked'] ?? 0);
        $refused   = (int) ($consumers['refused'] ?? 0);

        // What each side's average is a mean over — `completed` for requests, `timed` for
        // consumers — which is what the two have to be weighted by to be averaged together.
        $measured = $completed + (int) ($consumers['timed'] ?? 0);

        $totalMs = $completed * (float) ($requests['avgMs'] ?? 0)
            + (int) ($consumers['timed'] ?? 0) * (float) ($consumers['avgMs'] ?? 0);

        return new SconcurWorkObject(
            inProcess: (int) ($requests['inFlight'] ?? 0) + (int) ($consumers['inFlight'] ?? 0),
            inProcess1to5s: (int) ($requests['inFlight1to5s'] ?? 0) + (int) ($consumers['inFlight1to5s'] ?? 0),
            inProcess5to15s: (int) ($requests['inFlight5to15s'] ?? 0) + (int) ($consumers['inFlight5to15s'] ?? 0),
            inProcessOver15s: (int) ($requests['inFlightOver15s'] ?? 0) + (int) ($consumers['inFlightOver15s'] ?? 0),
            // A request that ended with a 500 is still counted by `completed`, so the
            // counterpart on the consumer side is everything that settled, refused
            // deliveries included — not `acked` alone.
            finished: $completed + $acked + $refused,
            refused: $refused,
            measured: $measured,
            avgMs: $measured > 0 ? $totalMs / $measured : 0.0,
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
            runtimeTasks: 0,
            work: null,
            masterCpuPercent: 0.0,
            masterMemoryRssBytes: 0,
            groups: [],
            workers: [],
        );
    }
}
