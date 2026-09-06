<?php

namespace Tests\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Infrastructure\Http\Controllers\DataFilterParameterTransport;
use PHPUnit\Framework\TestCase;

/**
 * The `data` section of a trace search, as it arrives from the request.
 *
 * It is optional — RequestFilterRules::data() says `sometimes` — so the shape the
 * transport has to survive is not only the filled one. A search with no data filter at
 * all is the ordinary case, and it used to answer with a 500.
 */
class DataFilterParameterTransportTest extends TestCase
{
    public function testARequestWithoutTheDataSectionFiltersOnNothing(): void
    {
        $parameters = new DataFilterParameterTransport()->make(['page' => 1]);

        $this->assertSame([], $parameters->filter);
        $this->assertSame([], $parameters->fields);
    }

    /** `data: {}` and no `data` at all mean the same thing and must not diverge. */
    public function testAnEmptyDataSectionIsTheSameAsNone(): void
    {
        $parameters = new DataFilterParameterTransport()->make(['data' => []]);

        $this->assertSame([], $parameters->filter);
        $this->assertSame([], $parameters->fields);
    }

    /** Half a section is still a section: fields without filter, and the other way round. */
    public function testEitherHalfOfTheSectionMayBeMissing(): void
    {
        $fieldsOnly = new DataFilterParameterTransport()->make(['data' => ['fields' => ['a', 'b']]]);

        $this->assertSame([], $fieldsOnly->filter);
        $this->assertSame(['a', 'b'], $fieldsOnly->fields);

        $filterOnly = new DataFilterParameterTransport()->make([
            'data' => ['filter' => [['field' => 'x', 'null' => true]]],
        ]);

        $this->assertCount(1, $filterOnly->filter);
        $this->assertSame([], $filterOnly->fields);
    }

    /**
     * The filled case, so a guard added for the empty one cannot quietly start dropping
     * what callers actually send.
     */
    public function testAFilledSectionIsCarriedThroughIntact(): void
    {
        $parameters = new DataFilterParameterTransport()->make([
            'data' => [
                'fields' => ['user.id'],
                'filter' => [
                    ['field' => 'code', 'numeric' => ['value' => 500, 'comp' => '>=']],
                    ['field' => 'name', 'string' => ['value' => 'boom', 'comp' => 'contains']],
                    ['field' => 'ok', 'boolean' => ['value' => true]],
                    ['field' => 'missing', 'null' => true],
                ],
            ],
        ]);

        $this->assertSame(['user.id'], $parameters->fields);
        $this->assertCount(4, $parameters->filter);

        $this->assertSame('code', $parameters->filter[0]->field);
        $this->assertSame(500, $parameters->filter[0]->numeric?->value);
        $this->assertSame(TraceDataFilterCompNumericTypeEnum::Gte, $parameters->filter[0]->numeric?->comp);

        $this->assertSame('boom', $parameters->filter[1]->string?->value);
        $this->assertSame(TraceDataFilterCompStringTypeEnum::Con, $parameters->filter[1]->string?->comp);

        $this->assertTrue($parameters->filter[2]->boolean?->value);
        $this->assertTrue($parameters->filter[3]->null);
    }
}
