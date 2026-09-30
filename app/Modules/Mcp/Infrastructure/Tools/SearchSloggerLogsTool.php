<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpLogEntriesAction;
use App\Modules\Mcp\Domain\Exceptions\McpLogLevelNotFoundException;
use App\Modules\Mcp\Domain\Exceptions\McpLogSourceNotFoundException;
use App\Modules\Mcp\Entities\Bridges\McpLogEntryObject;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Mcp\Parameters\FindMcpLogEntriesParameters;

readonly class SearchSloggerLogsTool implements McpToolInterface
{
    private const int DEFAULT_LIMIT = 20;

    public function __construct(
        private FindMcpLogEntriesAction $findMcpLogEntriesAction,
        private McpToolTimeParser $timeParser,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'search_slogger_logs';
    }

    public function title(): string
    {
        return 'SLogger logs';
    }

    public function description(): string
    {
        return 'Log entries of SLogger itself (its Laravel app, nginx, receiver), NOT of the client services: '
            . 'use it only for questions about how SLogger works. Newest first, by source, levels, period '
            . 'and text. While the files of a source are being indexed the answer is "indexing": repeat the '
            . 'call later.';
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            new McpToolProperty(
                name: 'source',
                type: McpToolPropertyTypeEnum::String,
                description: 'Log source of SLogger, for example Laravel, Slogger, Nginx, Receiver.',
                required: true,
                max: 255
            ),
            new McpToolProperty(
                name: 'levels',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Level names of the source, for example error, critical, warn, 5xx.',
                max: 20
            ),
            new McpToolProperty(
                name: 'from',
                type: McpToolPropertyTypeEnum::String,
                description: 'Start of the period, ISO 8601.',
                max: 64
            ),
            new McpToolProperty(
                name: 'to',
                type: McpToolPropertyTypeEnum::String,
                description: 'End of the period, ISO 8601.',
                max: 64
            ),
            new McpToolProperty(
                name: 'query',
                type: McpToolPropertyTypeEnum::String,
                description: 'Text to search for.',
                max: 255
            ),
            new McpToolProperty(
                name: 'limit',
                type: McpToolPropertyTypeEnum::Integer,
                description: sprintf('Entries to return, %d by default.', self::DEFAULT_LIMIT),
                min: 1,
                max: FindMcpLogEntriesAction::MAX_LIMIT
            ),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $from = null;
        $to   = null;

        if ($arguments->has('from')) {
            $from = $this->timeParser->parse($arguments->string('from'));

            if (is_null($from)) {
                return $this->formatter->invalidTime('from');
            }
        }

        if ($arguments->has('to')) {
            $to = $this->timeParser->parse($arguments->string('to'));

            if (is_null($to)) {
                return $this->formatter->invalidTime('to');
            }
        }

        try {
            $result = $this->findMcpLogEntriesAction->handle(
                new FindMcpLogEntriesParameters(
                    source: $arguments->string('source'),
                    levels: $arguments->stringList('levels'),
                    from: $from,
                    to: $to,
                    query: $arguments->stringNull('query'),
                    limit: $arguments->intNull('limit') ?? self::DEFAULT_LIMIT
                )
            );
        } catch (McpLogSourceNotFoundException $exception) {
            return $this->formatter->error(
                error: 'source_not_found',
                hint: 'Known sources: ' . implode(', ', $exception->sources) . '.'
            );
        } catch (McpLogLevelNotFoundException $exception) {
            return $this->formatter->error(
                error: 'level_not_found',
                hint: sprintf(
                    'Level "%s" is not a level of this source. Levels: %s.',
                    $exception->level,
                    implode(', ', $exception->levels)
                )
            );
        }

        if ($result->indexing) {
            return new McpToolResult(
                data: [
                    'status'              => 'indexing',
                    'retry_after_seconds' => 5,
                    'hint'                => 'The log files of this source are being indexed. Repeat the SAME call later.',
                ]
            );
        }

        return new McpToolResult(
            data: [
                'total'   => $result->total,
                'entries' => array_map(
                    fn(McpLogEntryObject $entry) => [
                        'time'  => $this->formatter->time($entry->time),
                        'level' => $entry->level,
                        'file'  => $entry->file,
                        'text'  => $entry->text,
                    ],
                    $result->entries
                ),
            ]
        );
    }
}
