<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpIncidentEventsAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpIncidentsAction;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\GetIncidentEventsTool;
use App\Modules\Mcp\Infrastructure\Tools\GetIncidentsTool;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentEventsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Parameters\FindIncidentsParameters;
use PHPUnit\Framework\TestCase;
use App\Modules\Mcp\Transports\McpIncidentObjectTransport;
use App\Modules\Mcp\Transports\McpIncidentEventObjectTransport;
use Tests\Modules\Watcher\WatcherIncidentEventFactoryTrait;
use Tests\Modules\Watcher\WatcherTypeRegistryFactoryTrait;

class IncidentToolsTest extends TestCase
{
    use McpToolTestTrait;

    use WatcherIncidentEventFactoryTrait;

    use WatcherTypeRegistryFactoryTrait;

    public function testListsIncidentsWithTheirWatchers(): void
    {
        $incidents = $this->createMock(FindIncidentsAction::class);
        $watchers  = $this->createMock(FindWatcherAction::class);

        $incidents
            ->method('handle')
            ->willReturnCallback(
                fn(FindIncidentsParameters $parameters) => match ($parameters->perPage) {
                    50 => [
                        $this->incident(
                            id: 'inc-1',
                        ),
                        $this->incident(
                            id: 'inc-2',
                        ),
                    ],
                    default => [
                        $this->incident(
                            id: 'inc-51',
                        ),
                    ],
                }
            );

        $watchers
            ->expects($this->once())
            ->method('handle')
            ->with(4, true)
            ->willReturn(
                $this->watcherWithServices(
                    serviceIds: [
                        7,
                    ],
                )
            );

        $result = (new GetIncidentsTool(
            findMcpIncidentsAction: new FindMcpIncidentsAction(
                findIncidentsAction: $incidents,
                findWatcherAction: $watchers,
                mcpIncidentObjectTransport: new McpIncidentObjectTransport(),
            ),
            formatter: new McpToolFormatter(),
        ))->call(new McpToolArguments([
            'status' => 'opened',
        ]));

        $this->assertTrue($result->data['has_more']);

        $this->assertCount(2, $result->data['incidents']);

        $this->assertSame([
            'id'             => 'inc-1',
            'status'         => 'opened',
            'first_event_at' => '2026-09-28T10:00:00Z',
            'last_event_at'  => '2026-09-28T11:00:00Z',
            'events_count'   => 3,
            'closed_at'      => null,
            'watcher'        => [
                'id'          => 4,
                'name'        => 'buffer',
                'type'        => 'bufferOverflow',
                'service_ids' => [
                    7,
                ],
            ],
        ], $result->data['incidents'][0]);
    }

    public function testStatusAndNextPageAreAskedFor(): void
    {
        $incidents = $this->createMock(FindIncidentsAction::class);
        $calls     = [];

        $incidents
            ->method('handle')
            ->willReturnCallback(static function (FindIncidentsParameters $parameters) use (&$calls) {
                $calls[] = [
                    $parameters->status,
                    $parameters->page,
                    $parameters->perPage,
                ];

                return [];
            });

        $result = (new GetIncidentsTool(
            findMcpIncidentsAction: new FindMcpIncidentsAction(
                findIncidentsAction: $incidents,
                findWatcherAction: $this->createMock(FindWatcherAction::class),
                mcpIncidentObjectTransport: new McpIncidentObjectTransport(),
            ),
            formatter: new McpToolFormatter(),
        ))->call(new McpToolArguments([
            'status' => 'closed',
            'page'   => 2,
        ]));

        $this->assertFalse($result->data['has_more']);

        $this->assertSame([
            [
                WatcherIncidentStatusEnum::Closed,
                2,
                50,
            ],
            [
                WatcherIncidentStatusEnum::Closed,
                101,
                1,
            ],
        ], $calls);
    }

    /** @return iterable<string, array{bool, bool}> */
    public static function incidentEventCases(): iterable
    {
        yield 'numbers' => [
            true,
            true,
        ];

        yield 'no events' => [
            true,
            false,
        ];

        yield 'deleted watcher' => [
            false,
            false,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('incidentEventCases')]
    public function testIncidentEventsCarryNumbers(bool $hasWatcher, bool $hasEvents): void
    {
        $incident = $this->createMock(FindIncidentAction::class);
        $watchers = $this->createMock(FindWatcherAction::class);
        $events   = $this->createMock(FindIncidentEventsAction::class);

        $incident->method('handle')->willReturn($this->incident());

        $watchers->method('handle')->willReturn($hasWatcher ? $this->watcherWithServices() : null);

        $events
            ->method('handle')
            ->willReturnCallback(
                fn(string $incidentId, $type, int $page, int $perPage) => $hasEvents && $perPage === 50 ? [
                    $this->incidentEvent(),
                ] : []
            );

        $result = (new GetIncidentEventsTool(
            findMcpIncidentEventsAction: new FindMcpIncidentEventsAction(
                findIncidentAction: $incident,
                findWatcherAction: $watchers,
                findIncidentEventsAction: $events,
                watcherTypeRegistry: $this->watcherTypeRegistry(),
                mcpIncidentObjectTransport: new McpIncidentObjectTransport(),
                mcpIncidentEventObjectTransport: new McpIncidentEventObjectTransport(),
            ),
            formatter: new McpToolFormatter(),
        ))->call(new McpToolArguments([
            'incident_id' => 'inc-1',
        ]));

        $this->assertFalse($result->isError);

        $this->assertSame('inc-1', $result->data['incident']['id']);

        if (!$hasWatcher) {
            $this->assertNull($result->data['incident']['watcher']);
        }

        $this->assertSame($hasEvents ? [
            [
                'occurred_at' => '2026-09-08T19:20:03Z',
                'numbers'     => [
                    'settings' => [
                        'threshold' => 1000,
                    ],
                    'measured' => [
                        'buffer_count' => 12000,
                    ],
                ],
            ],
        ] : [], $result->data['events']);

        $this->assertFalse($result->data['has_more']);
    }

    public function testUnknownIncidentIsAToolError(): void
    {
        $incident = $this->createMock(FindIncidentAction::class);

        $incident->method('handle')->willReturn(null);

        $result = (new GetIncidentEventsTool(
            findMcpIncidentEventsAction: new FindMcpIncidentEventsAction(
                findIncidentAction: $incident,
                findWatcherAction: $this->createMock(FindWatcherAction::class),
                findIncidentEventsAction: $this->createMock(FindIncidentEventsAction::class),
                watcherTypeRegistry: $this->watcherTypeRegistry(),
                mcpIncidentObjectTransport: new McpIncidentObjectTransport(),
                mcpIncidentEventObjectTransport: new McpIncidentEventObjectTransport(),
            ),
            formatter: new McpToolFormatter(),
        ))->call(new McpToolArguments([
            'incident_id' => 'nope',
        ]));

        $this->assertTrue($result->isError);

        $this->assertSame('incident_not_found', $result->data['error']);
    }
}
