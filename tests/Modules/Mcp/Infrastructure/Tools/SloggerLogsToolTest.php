<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Logs\Domain\Actions\FindLogEntriesAction;
use App\Modules\Logs\Domain\Actions\FindLogFilesAction;
use App\Modules\Logs\Domain\Services\Formats\LogFormatRegistry;
use App\Modules\Logs\Entities\Entry\LogEntriesPageObject;
use App\Modules\Logs\Entities\Entry\LogEntryDetailsObject;
use App\Modules\Logs\Entities\Entry\LogEntryViewObject;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use App\Modules\Logs\Enums\LogTypeEnum;
use App\Modules\Logs\Parameters\FindLogEntriesParameters;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpLogEntriesAction;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\McpToolFormatter;
use App\Modules\Mcp\Infrastructure\Tools\McpToolTimeParser;
use App\Modules\Mcp\Infrastructure\Tools\SloggerLogsTool;
use Tests\TestCase;

class SloggerLogsToolTest extends TestCase
{
    private ?FindLogEntriesParameters $captured = null;

    public function testErrorsOfASourceNewestFirst(): void
    {
        $result = $this->callTool([
            'source' => 'laravel',
            'levels' => ['error', 'Critical'],
            'from'   => '2026-09-28T19:00:00Z',
            'to'     => '2026-09-28T20:00:00Z',
            'query'  => 'refused',
            'limit'  => 5,
        ]);

        $this->assertFalse($result->isError);
        $this->assertSame(
            [
                'total'   => 1,
                'entries' => [
                    [
                        'time'  => '2026-09-28T19:38:46Z',
                        'level' => 'ERROR',
                        'file'  => 'laravel.log',
                        'text'  => 'connect: IO error: Connection refused',
                    ],
                ],
            ],
            $result->data
        );
        $this->assertSame(['f-laravel'], $this->captured?->fileIds);
        $this->assertSame(['laravel.ERROR', 'laravel.CRITICAL'], $this->captured->levelKeys);
        $this->assertSame(1790622000, $this->captured->fromTime);
        $this->assertSame('refused', $this->captured->searchQuery);
        $this->assertSame(LogCursorDirectionEnum::Older, $this->captured->direction);
        $this->assertSame(5, $this->captured->perPage);
        $this->assertNull($this->captured->cursor);
    }

    public function testWithoutLevelsNoLevelFilter(): void
    {
        $this->callTool(['source' => 'Laravel']);

        $this->assertNull($this->captured?->levelKeys);
        $this->assertSame(20, $this->captured->perPage);
    }

    public function testUnknownSource(): void
    {
        $result = $this->callTool(['source' => 'Mongo']);

        $this->assertTrue($result->isError);
        $this->assertSame('source_not_found', $result->data['error']);
        $this->assertStringContainsString('Laravel, Nginx', $result->data['hint']);
        $this->assertNull($this->captured);
    }

    public function testUnknownLevel(): void
    {
        $result = $this->callTool(['source' => 'Laravel', 'levels' => ['fatal']]);

        $this->assertSame('level_not_found', $result->data['error']);
        $this->assertStringContainsString('EMERGENCY', $result->data['hint']);
    }

    public function testInvalidTime(): void
    {
        $this->assertSame('invalid_time', $this->callTool(['source' => 'Laravel', 'from' => 'today'])->data['error']);
    }

    public function testIndexing(): void
    {
        $result = $this->callTool(['source' => 'Laravel'], indexing: true);

        $this->assertFalse($result->isError);
        $this->assertSame('indexing', $result->data['status']);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function callTool(array $arguments, bool $indexing = false): McpToolResult
    {
        $files = $this->createMock(FindLogFilesAction::class);
        $files->method('handle')->willReturn([
            $this->file('f-laravel', 'laravel.log', 'Laravel', LogTypeEnum::Laravel),
            $this->file('f-access', 'access.log', 'Nginx', LogTypeEnum::NginxAccess),
        ]);

        $entries = $this->createMock(FindLogEntriesAction::class);
        $entries->method('handle')->willReturnCallback(function (FindLogEntriesParameters $parameters) use ($indexing) {
            $this->captured = $parameters;

            return new LogEntriesPageObject(
                items: $indexing ? [] : [
                    new LogEntryViewObject(
                        fileId: 'f-laravel',
                        type: LogTypeEnum::Laravel,
                        entryNo: 3,
                        loggedAt: 1790624326,
                        levelKey: 'laravel.ERROR',
                        details: new LogEntryDetailsObject(message: 'connect', context: null, fields: []),
                        text: 'connect: IO error: Connection refused',
                        truncated: false
                    ),
                ],
                levelCounts: [],
                total: $indexing ? 0 : 1,
                scanned: 1,
                indexing: $indexing,
                indexedBytes: 0,
                totalBytes: 0,
                restartedFileIds: [],
                missingFileIds: [],
                olderCursor: null,
                newerCursor: null
            );
        });

        return new SloggerLogsTool(
            new FindMcpLogEntriesAction($files, $entries, $this->app->make(LogFormatRegistry::class)),
            new McpToolTimeParser(),
            new McpToolFormatter()
        )->call(new McpToolArguments($arguments));
    }

    private function file(string $id, string $name, string $source, LogTypeEnum $type): LogFileObject
    {
        return new LogFileObject(
            id: $id,
            path: "/logs/$name",
            name: $name,
            source: $source,
            folder: '/logs',
            type: $type,
            sizeBytes: 10,
            modifiedAtMs: 1
        );
    }
}
