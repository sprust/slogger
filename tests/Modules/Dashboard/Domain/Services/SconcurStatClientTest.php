<?php

namespace Tests\Modules\Dashboard\Domain\Services;

use App\Modules\Dashboard\Domain\Services\SconcurStatClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

/**
 * The panel only knows workers that push telemetry to it, and pushing is done by the Go
 * side of the server and consumer runtimes. A group of plain artisan workers — the
 * periodic task pool — reports nothing, so it is configured, supervised, and missing
 * from the panel's answer. Dropping it from the dashboard would read as "there is no
 * such pool".
 */
class SconcurStatClientTest extends TestCase
{
    protected function setUp(): void
    {
        $container = new Container();
        $container->instance('config', new Repository([
            'sconcur' => [
                'panel_host' => 'http://panel/api/stats',
                'master'     => [
                    'adminToken' => 'token',
                    'groups'     => [
                        ['name' => 'http'],
                        ['name' => 'rabbitmq'],
                        ['name' => 'tasks'],
                    ],
                ],
            ],
        ]));

        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function testAGroupThePanelDoesNotReportIsShownWithZeros(): void
    {
        $stat = $this->client([
            'groups' => [
                ['name' => 'http', 'workersTotal' => 2, 'totals' => ['cpuPercent' => 7.5]],
            ],
        ])->find();

        $this->assertSame(['http', 'rabbitmq', 'tasks'], $this->names($stat->groups));

        $tasks = $stat->groups[2];

        $this->assertSame(0, $tasks->workersTotal);
        $this->assertSame(0.0, $tasks->cpuPercent);
        $this->assertSame(0, $tasks->memoryRssBytes);
        $this->assertSame(0, $tasks->goroutines);

        // Absent rather than zeroed: the dashboard renders a dash for it, which says
        // "this pool does not count that" instead of "none happened".
        $this->assertNull($tasks->work);
    }

    public function testAReportedGroupKeepsItsOwnNumbers(): void
    {
        $stat = $this->client([
            'groups' => [
                ['name' => 'http', 'workersTotal' => 2, 'totals' => ['cpuPercent' => 7.5]],
            ],
        ])->find();

        $this->assertSame(2, $stat->groups[0]->workersTotal);
        $this->assertSame(7.5, $stat->groups[0]->cpuPercent);
    }

    /**
     * The panel builds its answer from a map, so it hands the pools over in whatever order
     * the runtime iterated them — and that order changes between calls, which on a page
     * refreshing every second swapped the rows under the cursor. The config is the one
     * stable order there is.
     */
    public function testGroupsFollowTheOrderOfTheMasterConfig(): void
    {
        $stat = $this->client([
            'groups' => [
                ['name' => 'tasks'],
                ['name' => 'rabbitmq'],
                ['name' => 'http'],
            ],
        ])->find();

        $this->assertSame(['http', 'rabbitmq', 'tasks'], $this->names($stat->groups));
    }

    /** A group renamed while the master kept running is still shown, after the known ones. */
    public function testAGroupTheConfigDoesNotNameGoesLast(): void
    {
        $stat = $this->client([
            'groups' => [
                ['name' => 'legacy'],
                ['name' => 'tasks'],
                ['name' => 'http'],
            ],
        ])->find();

        $this->assertSame(['http', 'rabbitmq', 'tasks', 'legacy'], $this->names($stat->groups));
    }

    /**
     * The counters the panel does report have to arrive intact. Everything above only
     * exercises the absent case, so a renamed panel key would leave the dashboard showing
     * zeroes while the null-vs-absent tests still passed.
     */
    public function testAnHttpPoolsRequestsBecomeTheWorkSection(): void
    {
        $stat = $this->client([
            'groups' => [
                [
                    'name'         => 'http',
                    'workersTotal' => 2,
                    'totals'       => [
                        'requests' => [
                            'completed'       => 120,
                            'avgMs'           => 12.5,
                            'inFlight'        => 3,
                            'inFlight1to5s'   => 2,
                            'inFlight5to15s'  => 1,
                            'inFlightOver15s' => 0,
                        ],
                    ],
                ],
            ],
        ])->find();

        $work = $stat->groups[0]->work;

        $this->assertNotNull($work);
        $this->assertSame(120, $work->finished);
        $this->assertSame(120, $work->measured);
        $this->assertSame(12.5, $work->avgMs);
        $this->assertSame(0, $work->refused);
        $this->assertSame(3, $work->inProcess);
        $this->assertSame(2, $work->inProcess1to5s);
        $this->assertSame(1, $work->inProcess5to15s);
        $this->assertSame(0, $work->inProcessOver15s);
    }

    /**
     * A refused delivery has finished — it just finished badly — and its request-side
     * counterpart `completed` counts a failed request the same way. Leaving it out of
     * Finished would make the column mean something different for a consumer pool than
     * for a server one.
     */
    public function testAConsumerPoolsFinishedCountsRefusedDeliveriesToo(): void
    {
        $stat = $this->client([
            'groups' => [
                [
                    'name'         => 'rabbitmq',
                    'workersTotal' => 1,
                    'totals'       => [
                        'consumers' => [
                            'coroutines'    => 8,
                            'delivered'     => 52,
                            'acked'         => 47,
                            'refused'       => 3,
                            'timed'         => 50,
                            'avgMs'         => 4.25,
                            'inFlight'      => 2,
                            'inFlight1to5s' => 1,
                        ],
                    ],
                ],
            ],
        ])->find();

        $work = $stat->groups[1]->work;

        $this->assertNotNull($work);
        $this->assertSame(50, $work->finished);
        $this->assertSame(3, $work->refused);
        // Not `finished`: an auto-acked delivery settles with no duration to measure, and
        // avgMs is a mean over the ones that have it.
        $this->assertSame(50, $work->measured);
        $this->assertSame(4.25, $work->avgMs);
        $this->assertSame(2, $work->inProcess);
        $this->assertSame(1, $work->inProcess1to5s);
    }

    /**
     * No pool sends both sections, but the master's totals are the sum of unlike pools and
     * do. The two averages are means over different counts, so they are weighted by those
     * counts rather than averaged as two numbers.
     */
    public function testTheMasterTotalsWeighBothSectionsIntoOneAverage(): void
    {
        $stat = $this->client([
            'totals' => [
                'requests'  => ['completed' => 100, 'avgMs' => 10.0, 'inFlight' => 4],
                'consumers' => [
                    'acked'    => 90,
                    'refused'  => 10,
                    'timed'    => 100,
                    'avgMs'    => 20.0,
                    'inFlight' => 6,
                ],
            ],
        ])->find();

        $work = $stat->work;

        $this->assertNotNull($work);
        $this->assertSame(200, $work->finished);
        $this->assertSame(200, $work->measured);
        $this->assertSame(15.0, $work->avgMs);
        $this->assertSame(10, $work->inProcess);
        $this->assertSame(10, $work->refused);
    }

    /**
     * A worker's counters are not nested under `totals` the way a group's are — the one
     * asymmetry in the panel's payload, and the easiest thing to get wrong.
     */
    public function testAWorkersCountersAreReadFromTheWorkerItself(): void
    {
        $stat = $this->client([
            'groups'  => [['name' => 'http', 'workersTotal' => 1]],
            'workers' => [
                [
                    'pid'      => 4242,
                    'group'    => 'http',
                    'memory'   => ['rssBytes' => 1048576],
                    'requests' => ['completed' => 9, 'inFlight' => 1],
                ],
            ],
        ])->find();

        $worker = $stat->workers[0];

        $this->assertSame(4242, $worker->pid);
        $this->assertSame('http', $worker->group);
        $this->assertSame(1048576, $worker->memoryRssBytes);
        $this->assertNotNull($worker->work);
        $this->assertSame(9, $worker->work->finished);
        $this->assertSame(1, $worker->work->inProcess);
    }

    /** The two tables read in the same order, and a worker keeps its row between refreshes. */
    public function testWorkersFollowTheGroupOrderAndThenThePid(): void
    {
        $stat = $this->client([
            'workers' => [
                ['pid' => 19, 'group' => 'tasks'],
                ['pid' => 17, 'group' => 'http'],
                ['pid' => 63, 'group' => 'rabbitmq'],
                ['pid' => 16, 'group' => 'http'],
            ],
        ])->find();

        $pids = array_map(static fn($worker): int => $worker->pid, $stat->workers);

        $this->assertSame([16, 17, 63, 19], $pids);
    }

    /**
     * @param object[] $groups
     *
     * @return string[]
     */
    private function names(array $groups): array
    {
        return array_map(static fn($group): string => $group->name, $groups);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function client(array $payload): SconcurStatClient
    {
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], (string) json_encode($payload)),
        ]));

        return new SconcurStatClient(new Client(['handler' => $handler]));
    }
}
