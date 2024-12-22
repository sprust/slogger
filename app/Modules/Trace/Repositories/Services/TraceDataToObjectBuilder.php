<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use Illuminate\Support\Arr;

class TraceDataToObjectBuilder
{
    private string $key;

    public function __construct(private readonly array $data)
    {
    }

    public function build(): TraceDataObject
    {
        $this->key = '';

        return $this->buildRecursive($this->data);
    }

    private function buildRecursive(array|string|bool|int|float|null $data): TraceDataObject
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

                $children[] = $this->buildRecursive($dataValue);

                $this->key = $currentKey;
            }

            return new TraceDataObject(
                key: $this->key,
                children: $children
            );
        }
    }
}
