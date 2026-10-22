<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use Illuminate\Support\Arr;

class TraceDataToObjectBuilder
{
    private const int FILTERABLE_LIST_DEPTH = 1;

    private string $key;

    /**
     * @param array<array-key, mixed>|string|bool|int|float|null $data data that is not an
     *                                                                 object is shown as it is
     */
    public function __construct(private readonly array|string|bool|int|float|null $data)
    {
    }

    public function build(): TraceDataObject
    {
        $this->key = '';

        return $this->buildRecursive(
            data: $this->data,
            filterKey: '',
            listDepth: 0
        );
    }

    /**
     * @param array<array-key, mixed>|string|bool|int|float|object|null $data
     */
    private function buildRecursive(
        array|string|bool|int|float|object|null $data,
        string $filterKey,
        int $listDepth
    ): TraceDataObject {
        $canBeFiltered = $listDepth <= self::FILTERABLE_LIST_DEPTH;

        if ($data === null) {
            return new TraceDataObject(
                key: $this->key,
                value: null,
                children: null,
                filterKey: $filterKey,
                canBeFiltered: $canBeFiltered
            );
        }

        if (is_object($data)) {
            return new TraceDataObject(
                key: $this->key,
                value: get_class($data),
                children: null,
                filterKey: $filterKey,
                canBeFiltered: $canBeFiltered
            );
        }

        if (!is_array($data)) {
            return new TraceDataObject(
                key: $this->key,
                value: $data,
                children: null,
                filterKey: $filterKey,
                canBeFiltered: $canBeFiltered
            );
        }

        $children = [];

        $isAssoc = Arr::isAssoc($data);

        foreach ($data as $dataKey => $dataValue) {
            $currentKey = $this->key;

            $key = $this->key !== '' ? "$this->key." : '';

            $this->key = "$key$dataKey";

            $children[] = $isAssoc
                ? $this->buildRecursive(
                    data: $dataValue,
                    filterKey: $filterKey === '' ? (string) $dataKey : "$filterKey.$dataKey",
                    listDepth: $listDepth
                )
                : $this->buildRecursive(
                    data: $dataValue,
                    filterKey: $filterKey,
                    listDepth: $filterKey === '' ? self::FILTERABLE_LIST_DEPTH + 1 : $listDepth + 1
                );

            $this->key = $currentKey;
        }

        return new TraceDataObject(
            key: $this->key,
            value: null,
            children: $children,
            filterKey: $filterKey,
            canBeFiltered: $canBeFiltered
        );
    }
}
