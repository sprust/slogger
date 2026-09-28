<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexBuildingException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexFailedException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceMetricsStepNotAllowedException;
use App\Modules\Mcp\Entities\Bridges\McpTraceMetricsObject;
use App\Modules\Mcp\Parameters\FindMcpTraceMetricsParameters;
use App\Modules\Trace\Domain\Actions\MakeTraceTimestampPeriodsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTimestampsAction;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexParallelArraysException;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampPeriodObject;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use App\Modules\Trace\Parameters\FindTraceTimestampsParameters;
use Illuminate\Support\Arr;

readonly class FindMcpTraceMetricsAction
{
    public const array PERIODS = [
        TraceTimestampPeriodEnum::Minute5,
        TraceTimestampPeriodEnum::Minute30,
        TraceTimestampPeriodEnum::Hour,
        TraceTimestampPeriodEnum::Hour4,
        TraceTimestampPeriodEnum::Hour12,
        TraceTimestampPeriodEnum::Day,
    ];

    public function __construct(
        private MakeTraceTimestampPeriodsAction $makeTraceTimestampPeriodsAction,
        private FindTraceTimestampsAction $findTraceTimestampsAction
    ) {
    }

    /**
     * @throws McpTraceMetricsStepNotAllowedException
     * @throws McpTraceIndexBuildingException
     * @throws McpTraceIndexFailedException
     */
    public function handle(FindMcpTraceMetricsParameters $parameters): McpTraceMetricsObject
    {
        $step = $parameters->step ?? $this->defaultStep($parameters->period);

        $allowedSteps = $this->allowedSteps($parameters->period);

        if (!in_array($step, $allowedSteps, true)) {
            throw new McpTraceMetricsStepNotAllowedException(
                period: $parameters->period,
                step: $step,
                allowedSteps: $allowedSteps
            );
        }

        try {
            $timestamps = $this->findTraceTimestampsAction->handle(
                new FindTraceTimestampsParameters(
                    timestampPeriod: $parameters->period,
                    timestampStep: $step,
                    fields: $parameters->metrics,
                    serviceIds: count($parameters->serviceIds) > 0 ? $parameters->serviceIds : null,
                    loggedAtTo: $parameters->to?->clone()->utc(),
                    types: $parameters->types,
                    tags: $parameters->tags,
                    statuses: $parameters->statuses,
                    durationFrom: $parameters->durationFrom,
                    durationTo: $parameters->durationTo,
                    memoryFrom: $parameters->memoryFrom,
                    memoryTo: $parameters->memoryTo,
                    cpuFrom: $parameters->cpuFrom,
                    cpuTo: $parameters->cpuTo
                )
            );

            return new McpTraceMetricsObject(
                step: $step,
                timestamps: $timestamps
            );
        } catch (TraceDynamicIndexInProcessException $exception) {
            throw new McpTraceIndexBuildingException($exception->indexId);
        } catch (TraceDynamicIndexErrorException $exception) {
            throw new McpTraceIndexFailedException($exception->getMessage());
        } catch (TraceDynamicIndexNotInitException) {
            throw new McpTraceIndexFailedException('The trace dynamic index could not be initialized.');
        } catch (TraceDynamicIndexParallelArraysException) {
            throw new McpTraceIndexFailedException('Tags and a data field cannot be filtered together.');
        }
    }

    private function defaultStep(TraceTimestampPeriodEnum $period): TraceTimestampEnum
    {
        return match ($period) {
            TraceTimestampPeriodEnum::Minute5  => TraceTimestampEnum::S5,
            TraceTimestampPeriodEnum::Minute30 => TraceTimestampEnum::S30,
            TraceTimestampPeriodEnum::Hour     => TraceTimestampEnum::Min,
            TraceTimestampPeriodEnum::Hour4    => TraceTimestampEnum::Min5,
            TraceTimestampPeriodEnum::Hour12,
            TraceTimestampPeriodEnum::Day      => TraceTimestampEnum::Min30,
            default                            => TraceTimestampEnum::D,
        };
    }

    /**
     * @return TraceTimestampEnum[]
     */
    private function allowedSteps(TraceTimestampPeriodEnum $period): array
    {
        /** @var TraceTimestampPeriodObject|null $found */
        $found = Arr::first(
            $this->makeTraceTimestampPeriodsAction->handle(),
            static fn(TraceTimestampPeriodObject $object) => $object->period === $period
        );

        if (is_null($found)) {
            return [];
        }

        return array_map(
            static fn(TraceTimestampObject $timestamp) => TraceTimestampEnum::from($timestamp->value),
            $found->timestamps
        );
    }
}
