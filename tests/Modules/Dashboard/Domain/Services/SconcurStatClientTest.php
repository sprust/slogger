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

        $names = array_map(static fn($group): string => $group->name, $stat->groups);

        $this->assertSame(['http', 'tasks'], $names);

        $tasks = $stat->groups[1];

        $this->assertSame(0, $tasks->workersTotal);
        $this->assertSame(0.0, $tasks->cpuPercent);
        $this->assertSame(0, $tasks->memoryRssBytes);
        $this->assertSame(0, $tasks->goroutines);

        // Both sections stay absent rather than zeroed: the dashboard renders a dash for
        // them, which says "this pool does not count these" instead of "none happened".
        $this->assertNull($tasks->requests);
        $this->assertNull($tasks->consumers);
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
     * The counters the panel does report have to arrive intact. Everything above only
     * exercises the absent case, so a renamed panel key would leave the dashboard showing
     * zeroes while the null-vs-absent tests still passed.
     */
    public function testAReportedGroupCarriesItsRequestAndConsumerCounters(): void
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
                [
                    'name'         => 'rabbitmq',
                    'workersTotal' => 1,
                    'totals'       => [
                        'consumers' => [
                            'coroutines' => 8,
                            'delivered'  => 50,
                            'acked'      => 47,
                            'refused'    => 3,
                            'timed'      => 1,
                            'avgMs'      => 4.25,
                            'inFlight'   => 2,
                        ],
                    ],
                ],
            ],
        ])->find();

        $requests = $stat->groups[0]->requests;

        $this->assertNotNull($requests);
        $this->assertSame(120, $requests->completed);
        $this->assertSame(12.5, $requests->avgMs);
        $this->assertSame(3, $requests->inFlight);
        $this->assertSame(2, $requests->inFlight1to5s);
        $this->assertSame(1, $requests->inFlight5to15s);
        $this->assertNull($stat->groups[0]->consumers, 'an http pool reports no consumers');

        $consumers = $stat->groups[1]->consumers;

        $this->assertNotNull($consumers);
        $this->assertSame(8, $consumers->coroutines);
        $this->assertSame(50, $consumers->delivered);
        $this->assertSame(47, $consumers->acked);
        $this->assertSame(3, $consumers->refused);
        $this->assertSame(1, $consumers->timed);
        $this->assertSame(4.25, $consumers->avgMs);
        $this->assertNull($stat->groups[1]->requests, 'a consumer pool reports no requests');
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
        $this->assertNotNull($worker->requests);
        $this->assertSame(9, $worker->requests->completed);
        $this->assertSame(1, $worker->requests->inFlight);
        $this->assertNull($worker->consumers);
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
