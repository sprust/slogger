<?php

namespace Tests\Dispatcher\Items\Queue\ApiClients\Socket;

use SLoggerLaravel\Dispatcher\Items\Queue\ApiClients\Socket\ArraySerializer;
use PHPUnit\Framework\TestCase;
use stdClass;

class ArraySerializerTest extends TestCase
{
    public function test()
    {
        $data = array_map(
            fn(int $index) => [
                'index'   => $index,
                'bool1'   => true,
                'bool2'   => false,
                'int1'    => 1,
                'int2'    => 2,
                'string1' => 'string1',
                'string2' => 'string2',
                'array'   => [
                    'bool1'   => true,
                    'bool2'   => false,
                    'int1'    => 1,
                    'int2'    => 2,
                    'string1' => 'string1',
                    'string2' => 'string2',
                    'array'   => [
                        1,
                        2,
                        3,
                    ],
                    'null'    => null,
                    'float1'  => 1.1,
                    'float2'  => 2.2,
                ],
                'null'    => null,
                'float1'  => 1.1,
                'float2'  => 2.2,
            ],
            range(1, 5)
        );

        $serializer = new ArraySerializer();

        $serialized = $serializer->serialize($data);

        $decoded = json_decode($serialized, true);

        self::assertArrayHasKey(
            'km',
            $decoded
        );
        self::assertArrayHasKey(
            'vm',
            $decoded
        );
        self::assertArrayHasKey(
            'i',
            $decoded
        );

        self::assertSameSize(
            $decoded['km'],
            array_unique(array_values($decoded['km']))
        );

        self::assertSameSize(
            $decoded['vm'],
            array_unique(array_values($decoded['vm']))
        );

        $deserialized = $serializer->deserialize($serialized);

        self::assertSame(
            $data,
            $deserialized
        );

        $data = [
            'object'   => new stdClass(),
        ];

        $serialized = $serializer->serialize($data);

        $deserialized = $serializer->deserialize($serialized);

        self::assertArrayHasKey(
            'object',
            $deserialized
        );

        self::assertTrue(
            $data['object'] instanceof stdClass,
        );
    }
}
