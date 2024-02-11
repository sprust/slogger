<?php

namespace App\Modules\TracesAggregator\Services;

use App\Modules\TracesAggregator\Dto\Objects\TraceDataNodeObject;
use Illuminate\Support\Arr;

class TraceDataConverter
{
    private string $key;

    public function __construct(private readonly array $data)
    {
    }

    public function convert(): TraceDataNodeObject
    {
        $this->key = '';

        return $this->convertRecursive($this->data);
    }

    private function convertRecursive(array|string|bool|int|float|null $data): TraceDataNodeObject
    {
        if (!is_array($data)) {
            return new TraceDataNodeObject(
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

            return new TraceDataNodeObject(
                key: $this->key,
                children: $children
            );
        }
    }
}
