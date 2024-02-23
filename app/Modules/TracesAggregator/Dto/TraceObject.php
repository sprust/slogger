<?php

namespace App\Modules\TracesAggregator\Dto;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceDataAdditionalFieldObject;
use Carbon\Carbon;
use Illuminate\Support\Arr;

readonly class TraceObject
{
    /**
     * @param string[]                         $tags
     * @param TraceDataAdditionalFieldObject[] $additionalFields
     */
    public function __construct(
        public ?TraceServiceObject $service,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public array $tags,
        public ?float $duration,
        public ?float $memory,
        public ?float $cpu,
        public array $additionalFields,
        public Carbon $loggedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }

    /**
     * @param string[] $additionalFields
     */
    public static function fromModel(Trace $trace, array $additionalFields = []): static
    {
        $additionalFieldValues = [];

        foreach ($additionalFields as $additionalField) {
            $additionalFieldData = explode('.', $additionalField);

            if (count($additionalFieldData) === 1) {
                $values = [Arr::get($trace->data, $additionalField)];
            } else {
                $preKey = implode('.', array_slice($additionalFieldData, 0, -1));

                $preValue = Arr::get($trace->data, $preKey);

                if (is_null($preValue)) {
                    continue;
                }

                if (Arr::isAssoc($preValue)) {
                    $values = [Arr::get($trace->data, $additionalField)];
                } else {
                    $key = $additionalFieldData[count($additionalFieldData) - 1];

                    $values = array_filter(
                        array_map(
                            fn(array $item) => $item[$key] ?? null,
                            $preValue
                        )
                    );
                }
            }

            $additionalFieldValues[] = new TraceDataAdditionalFieldObject(
                key: $additionalField,
                values: $values
            );
        }

        return new static(
            service: $trace->service
                ? new TraceServiceObject(
                    id: $trace->service->id,
                    name: $trace->service->name,
                )
                : null,
            traceId: $trace->traceId,
            parentTraceId: $trace->parentTraceId,
            type: $trace->type,
            tags: $trace->tags,
            duration: $trace->duration,
            memory: $trace->memory,
            cpu: $trace->cpu,
            additionalFields: $additionalFieldValues,
            loggedAt: $trace->loggedAt,
            createdAt: $trace->createdAt,
            updatedAt: $trace->updatedAt
        );
    }
}
