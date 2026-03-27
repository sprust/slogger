<?php

namespace Tests\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Repositories\Services\TraceDataToObjectBuilder;
use PHPUnit\Framework\TestCase;

class TraceDataToObjectBuilderTest extends TestCase
{
    public function test(): void
    {
        $data = [
            'arr' => [
                'key1' => 'value1',
                'key2' => [
                    'key3' => [
                        [
                            'key4' => 'value4',
                        ],
                    ],
                ],
            ],
        ];

        $expected = new TraceDataObject(
            key: "",
            value: null,
            children: [
                new TraceDataObject(
                    key: 'arr',
                    value: null,
                    children: [
                        new TraceDataObject(
                            key: 'arr.key1',
                            value: 'value1',
                            children: null,
                            canBeFiltered: true
                        ),
                        new TraceDataObject(
                            key: 'arr.key2',
                            value: null,
                            children: [
                                new TraceDataObject(
                                    key: 'arr.key2.key3',
                                    value: null,
                                    children: [
                                        new TraceDataObject(
                                            key: 'arr.key2.key3.0',
                                            value: null,
                                            children: [
                                                new TraceDataObject(
                                                    key: 'arr.key2.key3.0.key4',
                                                    value: 'value4',
                                                    children: null,
                                                    canBeFiltered: false
                                                ),
                                            ],
                                            canBeFiltered: false
                                        ),
                                    ],
                                    canBeFiltered: true
                                ),
                            ],
                            canBeFiltered: true
                        ),
                    ],
                    canBeFiltered: true
                ),
            ],
            canBeFiltered: true
        );

        $builder = new TraceDataToObjectBuilder($data);

        $expected = json_decode(json_encode($expected), true);
        $actual   = json_decode(json_encode($builder->build()), true);

        self::assertSame(
            expected: $expected,
            actual: $actual,
        );
    }
}
