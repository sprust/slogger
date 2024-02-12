<?php

namespace App\Modules\TracesAggregator\Dto;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceDataAdditionalFieldObject;
use App\Modules\TracesAggregator\Dto\Objects\TraceDataNodeObject;
use App\Modules\TracesAggregator\Services\TraceDataConverter;
use Carbon\Carbon;
use Illuminate\Support\Arr;

readonly class TraceObject
{
    /**
     * @param string[]                         $tags
     * @param TraceDataAdditionalFieldObject[] $additionalFields
     */
    public function __construct(
        public int $serviceId,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public array $tags,
        public TraceDataNodeObject $data,
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
            serviceId: $trace->serviceId,
            traceId: $trace->traceId,
            parentTraceId: $trace->parentTraceId,
            type: $trace->type,
            tags: $trace->tags,
            data: (new TraceDataConverter($trace->data))->convert(),
            additionalFields: $additionalFieldValues,
            loggedAt: $trace->loggedAt,
            createdAt: $trace->createdAt,
            updatedAt: $trace->updatedAt
        );
    }
}
