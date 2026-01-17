<?php

declare(strict_types=1);

namespace SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\Socket;

readonly class ArraySerializer
{
    /**
     * @param array<int|string, mixed> $array
     */
    public function serialize(array $array): string
    {
        $keysMap   = [];
        $valuesMap = [];
        $items     = [];

        $keysIndex   = 0;
        $valuesIndex = 0;

        foreach ($array as $key => $value) {
            $this->fillMap(
                currentKey: $key,
                keysIndex: $keysIndex,
                keysMap: $keysMap,
                valuesIndex: $valuesIndex,
                valuesMap: $valuesMap,
                data: $items,
                value: $value
            );
        }

        return json_encode([
            'km' => $keysMap,
            'vm' => $valuesMap,
            'i'  => $items,
        ]);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function deserialize(string $serialized): array
    {
        $decoded = json_decode($serialized, true);

        $keysMap   = $decoded['km'];
        $valuesMap = $decoded['vm'];
        $items     = $decoded['i'];

        $keys   = array_flip($keysMap);
        $values = array_flip($valuesMap);

        return $this->deserializeRecursive(
            data: $items,
            keys: $keys,
            values: $values
        );
    }

    /**
     * @param array<int, int|array<int, mixed>> $data
     * @param array<int, int|string>            $keys
     * @param array<int, string>                $values
     *
     * @return array<int|string, mixed>
     */
    protected function deserializeRecursive(array $data, array $keys, array $values): array
    {
        $result = [];

        foreach ($data as $keyIndex => $valueIndex) {
            $originalKey = $keys[$keyIndex];

            if (is_array($valueIndex)) {
                $result[$originalKey] = $this->deserializeRecursive(
                    data: $valueIndex,
                    keys: $keys,
                    values: $values
                );
            } else {
                $encodedValue         = $values[$valueIndex];
                $result[$originalKey] = $this->decodeValue($encodedValue);
            }
        }

        return $result;
    }

    /**
     * @param array<int|string, int>                                              $keysMap
     * @param array<string, int>                                                  $valuesMap
     * @param array<int, int|array<int, mixed>>                                   $data
     * @param object|array<int, int|array<int, mixed>>|string|int|float|bool|null $value
     */
    protected function fillMap(
        mixed $currentKey,
        int &$keysIndex,
        array &$keysMap,
        int &$valuesIndex,
        array &$valuesMap,
        array &$data,
        object|array|string|int|float|bool|null $value
    ): void {
        if (array_key_exists($currentKey, $keysMap)) {
            $key = $keysMap[$currentKey];
        } else {
            ++$keysIndex;

            $keysMap[$currentKey] = $keysIndex;

            $key = $keysIndex;
        }

        if (is_array($value)) {
            $data[$key] = [];

            foreach ($value as $vKey => $vValue) {
                $this->fillMap(
                    currentKey: $vKey,
                    keysIndex: $keysIndex,
                    keysMap: $keysMap,
                    valuesIndex: $valuesIndex,
                    valuesMap: $valuesMap,
                    data: $data[$key],
                    value: $vValue
                );
            }

            return;
        }

        $value = $this->encodeValue($value);

        if (array_key_exists($value, $valuesMap)) {
            $newValueIndex = $valuesMap[$value];
        } else {
            ++$valuesIndex;

            $valuesMap[$value] = $valuesIndex;

            $newValueIndex = $valuesIndex;
        }

        $data[$key] = $newValueIndex;
    }

    protected function encodeValue(object|string|int|float|bool|null $value): string
    {
        if (is_object($value)) {
            $value = "o:" . json_encode($value);
        } elseif (is_null($value)) {
            $value = "n:_";
        } elseif (is_bool($value)) {
            $value = "b:$value";
        } elseif (is_int($value)) {
            $value = "i:$value";
        } elseif (is_float($value)) {
            $value = "f:$value";
        } else {
            $value = "s:$value";
        }

        return $value;
    }

    /**
     * @return array<int|string, mixed>|string|int|float|bool|null
     */
    protected function decodeValue(string $encoded): array|string|int|float|bool|null
    {
        $type  = substr($encoded, 0, 2);
        $value = substr($encoded, 2);

        return match ($type) {
            'o:' => json_decode($value, true),
            'n:' => null,
            'b:' => (bool) $value,
            'i:' => (int) $value,
            'f:' => (float) $value,
            default => $value,
        };
    }
}
