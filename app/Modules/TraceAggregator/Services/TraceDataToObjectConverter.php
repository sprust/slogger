<?php

namespace App\Modules\TraceAggregator\Services;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceDataObject;
use Illuminate\Support\Arr;

class TraceDataToObjectConverter
{
    private string $key;

    public function __construct(private readonly array $data)
    {
    }

    public function convert(): TraceDataObject
    {
        $this->key = '';

        return $this->convertRecursive($this->data);
    }

    private function convertRecursive(array|string|bool|int|float|null $data): TraceDataObject
    {
        if (!is_array($data)) {
            return new TraceDataObject(
                key: $this->key,
                value: $data
            );
        } else {
            $children = [];

            $isAssoc = Arr::isAssoc($data);

            foreach ($data as $dataKey => $dataValue) {
                $currentKey = $this->key;

                if ($isAssoc) {
                    $key = $this->key ? "$this->key." : '';

                    $this->key = "$key$dataKey";
                }

                $children[] = $this->convertRecursive($dataValue);

                $this->key = $currentKey;
            }

            return new TraceDataObject(
                key: $this->key,
                children: $children
            );
        }
    }
}
