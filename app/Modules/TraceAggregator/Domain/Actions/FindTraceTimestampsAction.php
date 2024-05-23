<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\Common\Enums\TraceTimestampTypeEnum;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\FindTraceTimestampsParameters;
use App\Modules\TraceAggregator\Enums\TimestampPeriodEnum;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTimestampsRepositoryInterface;
use RuntimeException;

readonly class FindTraceTimestampsAction
{
    public function __construct(
        private TraceTimestampsRepositoryInterface $traceTimestampsRepository
    ) {
    }

    public function handle(FindTraceTimestampsParameters $parameters): array
    {
        $loggedAtTo = $parameters->loggedAtTo ?? now('UTC');

        switch ($parameters->timestampPeriod) {
            case TimestampPeriodEnum::Minute5:
                $loggedAtFrom  = $loggedAtTo->clone()->subMinutes(5);
                $timestampType = TraceTimestampTypeEnum::S5;
                break;
            case TimestampPeriodEnum::Minute30:
                $loggedAtFrom  = $loggedAtTo->clone()->subMinutes(30);
                $timestampType = TraceTimestampTypeEnum::S10;
                break;
            case TimestampPeriodEnum::Hour:
                $loggedAtFrom  = $loggedAtTo->clone()->subHour();
                $timestampType = TraceTimestampTypeEnum::S30;
                break;
            case TimestampPeriodEnum::Hour4:
                $loggedAtFrom  = $loggedAtTo->clone()->subHours(4);
                $timestampType = TraceTimestampTypeEnum::Min;
                break;
            case TimestampPeriodEnum::Hour12:
                $loggedAtFrom  = $loggedAtTo->clone()->subHours(12);
                $timestampType = TraceTimestampTypeEnum::Min5;
                break;
            case TimestampPeriodEnum::Day:
                $loggedAtFrom  = $loggedAtTo->clone()->subDay();
                $timestampType = TraceTimestampTypeEnum::Min10;
                break;
            case TimestampPeriodEnum::Day3:
                $loggedAtFrom  = $loggedAtTo->clone()->subDays(3);
                $timestampType = TraceTimestampTypeEnum::Min30;
                break;
            case TimestampPeriodEnum::Day7:
                $loggedAtFrom  = $loggedAtTo->clone()->subDays(7);
                $timestampType = TraceTimestampTypeEnum::H;
                break;
            case TimestampPeriodEnum::Day15:
                $loggedAtFrom  = $loggedAtTo->clone()->subDays(15);
                $timestampType = TraceTimestampTypeEnum::H4;
                break;
            case TimestampPeriodEnum::Month:
                $loggedAtFrom  = $loggedAtTo->clone()->subMonth();
                $timestampType = TraceTimestampTypeEnum::H12;
                break;
            case TimestampPeriodEnum::Month3:
                $loggedAtFrom  = $loggedAtTo->clone()->subMonths(3);
                $timestampType = TraceTimestampTypeEnum::H12;
                break;
            case TimestampPeriodEnum::Month6:
                $loggedAtFrom  = $loggedAtTo->clone()->subMonths(6);
                $timestampType = TraceTimestampTypeEnum::D;
                break;
            case TimestampPeriodEnum::Year:
                $loggedAtFrom  = $loggedAtTo->clone()->subYear();
                $timestampType = TraceTimestampTypeEnum::M;
                break;
            default:
                throw new RuntimeException('Not implemented.');
        }

        $timestampsDtoList = $this->traceTimestampsRepository->find(
            timestampType: $timestampType,
            serviceIds: $parameters->serviceIds,
            traceIds: $parameters->traceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $parameters->types,
            tags: $parameters->tags,
            statuses: $parameters->statuses,
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            data: $parameters->data,
            hasProfiling: $parameters->hasProfiling,
        );

        return $timestampsDtoList;
    }
}
