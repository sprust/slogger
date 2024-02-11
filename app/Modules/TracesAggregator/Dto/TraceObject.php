<?php

namespace App\Modules\TracesAggregator\Dto;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceDataCustomFieldObject;
use App\Modules\TracesAggregator\Dto\Objects\TraceDataNodeObject;
use App\Modules\TracesAggregator\Services\TraceDataConverter;
use Carbon\Carbon;
use Illuminate\Support\Arr;

readonly class TraceObject
{
    public function __construct(
        public int $serviceId,
        public string $traceId,
        public ?string $parentTraceId,
        public string $type,
        public array $tags,
        public TraceDataNodeObject $data,
        public array $customFields,
        public Carbon $loggedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }

    public static function fromModel(Trace $trace, array $customFields = []): static
    {
        $customFieldValues = [];

        foreach ($customFields as $customField) {
            $customFieldData = explode('.', $customField);

            if (count($customFieldData) === 1) {
                $values = [Arr::get($trace->data, $customField)];
            } else {
                $preKey = implode('.', array_slice($customFieldData, 0, -1));

                $preValue = Arr::get($trace->data, $preKey);

                if (Arr::isAssoc($preValue)) {
                    $values = [Arr::get($trace->data, $customField)];
                } else {
                    $key = $customFieldData[count($customFieldData) - 1];

                    $values = array_filter(array_map(
                        fn(array $item) => $item[$key] ?? null,
                        $preValue
                    ));
                }
            }

            $customFieldValues[] = new TraceDataCustomFieldObject(
                key: $customField,
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
            customFields: $customFieldValues,
            loggedAt: $trace->loggedAt,
            createdAt: $trace->createdAt,
            updatedAt: $trace->updatedAt
        );
    }
}
