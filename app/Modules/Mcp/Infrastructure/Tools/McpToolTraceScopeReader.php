<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Exceptions\McpTraceInvalidPeriodException;
use App\Modules\Mcp\Domain\Exceptions\McpTracePeriodTooWideException;
use App\Modules\Mcp\Domain\Services\McpTracePeriodResolver;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;

readonly class McpToolTraceScopeReader
{
    public const string INDEX_NOTE = 'It builds a trace dynamic index in the background: while it is building '
        . 'the answer is "index_building", repeat the SAME call later. The index depends on which filters are '
        . 'set and the hours of the period, not on the filter values.';
    private const int MAX_SERVICES = 20;

    public function __construct(
        private McpToolTimeParser $timeParser,
        private McpToolServiceFinder $serviceFinder,
        private McpTracePeriodResolver $periodResolver,
        private McpToolFormatter $formatter
    ) {
    }

    /**
     * @return McpToolProperty[]
     */
    public function properties(): array
    {
        return [
            new McpToolProperty(
                name: 'service_ids',
                type: McpToolPropertyTypeEnum::IntegerList,
                description: 'Service ids from get_services. All services when omitted.',
                max: self::MAX_SERVICES
            ),
            new McpToolProperty(
                name: 'from',
                type: McpToolPropertyTypeEnum::String,
                description: 'Start of the period, ISO 8601. Rounded down to the hour (UTC).',
                required: true,
                max: 64
            ),
            new McpToolProperty(
                name: 'to',
                type: McpToolPropertyTypeEnum::String,
                description: sprintf(
                    'End of the period, ISO 8601. Rounded up to the hour (UTC); at most %d hours after "from".',
                    McpTracePeriodResolver::MAX_HOURS
                ),
                required: true,
                max: 64
            ),
        ];
    }

    public function read(McpToolArguments $arguments): McpToolTraceScopeObject|McpToolResult
    {
        $from = $this->timeParser->parse($arguments->string('from'));

        if (is_null($from)) {
            return $this->formatter->invalidTime('from');
        }

        $to = $this->timeParser->parse($arguments->string('to'));

        if (is_null($to)) {
            return $this->formatter->invalidTime('to');
        }

        $serviceIds = $arguments->intList('service_ids');

        $unknownServiceIds = $this->serviceFinder->findUnknownIds($serviceIds);

        if (count($unknownServiceIds) > 0) {
            return $this->formatter->serviceNotFound($unknownServiceIds);
        }

        try {
            $period = $this->periodResolver->resolve($from, $to);
        } catch (McpTraceInvalidPeriodException) {
            return $this->formatter->error(
                error: 'invalid_period',
                hint: '"from" is later than "to".'
            );
        } catch (McpTracePeriodTooWideException $exception) {
            return $this->formatter->error(
                error: 'period_too_wide',
                hint: sprintf(
                    'The period rounded to hours is longer than %d hours. Find the window you need with '
                    . 'aggregate_traces by hour first, then query at most %d hours.',
                    $exception->maxHours,
                    $exception->maxHours
                )
            );
        }

        return new McpToolTraceScopeObject(
            serviceIds: $serviceIds,
            period: $period
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function periodData(McpToolTraceScopeObject $scope): array
    {
        return [
            'from' => $this->formatter->time($scope->period->from),
            'to'   => $this->formatter->time($scope->period->to),
        ];
    }
}
