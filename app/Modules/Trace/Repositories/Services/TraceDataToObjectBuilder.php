<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use Illuminate\Support\Arr;

class TraceDataToObjectBuilder
{
    private string $key;

    /**
     * @param array<array-key, mixed> $data
     */
    public function __construct(private readonly array $data)
    {
    }

    public function build(): TraceDataObject
    {
        $this->key = '';

        return $this->buildRecursive(
            data: $this->data,
            canBeFiltered: true
        );
    }

    /**
     * @param array<array-key, mixed>|string|bool|int|float|object|null $data
     */
    private function buildRecursive(
        array|string|bool|int|float|object|null $data,
        bool $canBeFiltered
    ): TraceDataObject {
        if ($data === null) {
            return new TraceDataObject(
                key: $this->key,
                value: null,
                children: null,
                canBeFiltered: $canBeFiltered
            );
        }

        if (is_object($data)) {
            return new TraceDataObject(
                key: $this->key,
                value: get_class($data),
                children: null,
                canBeFiltered: $canBeFiltered
            );
        }

        if (!is_array($data)) {
            return new TraceDataObject(
                key: $this->key,
                value: $data,
                children: null,
                canBeFiltered: $canBeFiltered
            );
        } else {
            $children = [];

            $isAssoc = Arr::isAssoc($data);

            foreach ($data as $dataKey => $dataValue) {
                $currentKey = $this->key;

                $key = $this->key ? "$this->key." : '';

                $this->key = "$key$dataKey";

                $children[] = $this->buildRecursive(
                    data: $dataValue,
                    canBeFiltered: $canBeFiltered && $isAssoc
                );

                $this->key = $currentKey;
            }

            return new TraceDataObject(
                key: $this->key,
                value: null,
                children: $children,
                canBeFiltered: $canBeFiltered
            );
        }
    }
}
