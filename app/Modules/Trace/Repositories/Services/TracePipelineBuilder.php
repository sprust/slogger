<?php

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

class TracePipelineBuilder
{
    /**
     * @param int[]|null                $serviceIds
     * @param string[]|null             $traceIds
     * @param string[]                  $types
     * @param string[]                  $tags
     * @param string[]                  $statuses
     * @param string[]|null             $projectFields
     * @param array<string, mixed>|null $customMatch
     *
     * @return array<string, array<string, mixed>>[]
     */
    public function make(
        ?array $serviceIds = null,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
        ?array $projectFields = null,
        ?array $customMatch = null
    ): array {
        $match = [];

        if ($serviceIds) {
            $match['sid'] = ['$in' => $serviceIds];
        }

        if ($traceIds) {
            $match['tid'] = ['$in' => $traceIds];
        }

        if ($loggedAtFrom) {
            $match['lat']['$gte'] = new UTCDateTime($loggedAtFrom->clone()->startOfSecond());
        }

        if ($loggedAtTo) {
            $match['lat']['$lte'] = new UTCDateTime($loggedAtTo->clone()->endOfSecond());
        }

        if ($types) {
            $match['tp'] = ['$in' => $types];
        }

        if ($tags) {
            $match['tgs.nm'] = ['$in' => $tags];
        }

        if ($statuses) {
            $match['st'] = ['$in' => $statuses];
        }

        if (!is_null($durationFrom)) {
            $match['dur']['$gte'] = $durationFrom;
        }

        if (!is_null($durationTo)) {
            $match['dur']['$lte'] = $durationTo;
        }

        if (!is_null($memoryFrom)) {
            $match['mem']['$gte'] = $memoryFrom;
        }

        if (!is_null($memoryTo)) {
            $match['mem']['$lte'] = $memoryTo;
        }

        if (!is_null($cpuFrom)) {
            $match['cpu']['$gte'] = $cpuFrom;
        }

        if (!is_null($cpuTo)) {
            $match['cpu']['$lte'] = $cpuTo;
        }

        if (!is_null($hasProfiling)) {
            $match['hpr'] = $hasProfiling;
        }

        if (!is_null($customMatch)) {
            foreach ($customMatch as $key => $value) {
                $match[$key] = $value;
            }
        }

        if ($data) {
            foreach ($data->filter as $filterItem) {
                $field = $filterItem->field;

                if (!is_null($filterItem->null)) {
                    $match[$field] = $filterItem->null ? ['$exists' => false] : ['$exists' => true];

                    continue;
                }

                if (!is_null($filterItem->numeric)) {
                    $match[$field][$filterItem->numeric->comp->value] = $filterItem->numeric->value;

                    continue;
                }

                if (!is_null($filterItem->string)) {
                    $regex = match ($filterItem->string->comp) {
                        TraceDataFilterCompStringTypeEnum::Con => ".*{$filterItem->string->value}.*",
                        TraceDataFilterCompStringTypeEnum::Starts => "^{$filterItem->string->value}.*",
                        TraceDataFilterCompStringTypeEnum::Ends => ".*{$filterItem->string->value}$",
                        default => $filterItem->string->value,
                    };

                    $match[$field] = ['$regex' => $regex];

                    continue;
                }

                if (!is_null($filterItem->boolean)) {
                    $match[$field] = $filterItem->boolean->value;
                }
            }
        }

        $pipeline = [];

        if (count($match)) {
            $pipeline[] = ['$match' => $match];
        }

        if (!is_null($projectFields) && count($projectFields)) {
            $pipeline[] = [
                '$project' => array_fill_keys($projectFields, 1),
            ];
        }

        return $pipeline;
    }
}
