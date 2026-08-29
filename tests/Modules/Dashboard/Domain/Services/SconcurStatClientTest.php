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
