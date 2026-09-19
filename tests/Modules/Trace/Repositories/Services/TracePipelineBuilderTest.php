<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterStringParameters;
use App\Modules\Trace\Repositories\Services\TracePipelineBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TracePipelineBuilderTest extends TestCase
{
    #[DataProvider('stringFilterProvider')]
    public function testAStringFilterMatchesItsValueLiterally(
        TraceDataFilterCompStringTypeEnum $comp,
        string $expectedRegex
    ): void {
        $pipeline = (new TracePipelineBuilder())->make(
            data: new TraceDataFilterParameters(
                filter: [
                    new TraceDataFilterItemParameters(
                        field: 'dt.job',
                        null: null,
                        exists: null,
                        numeric: null,
                        string: new TraceDataFilterStringParameters(
                            value: 'App\Modules\ExcelExport\Jobs\GenerateExcelExportJob.run',
                            comp: $comp
                        ),
                        boolean: null
                    ),
                ]
            )
        );

        self::assertSame(
            [['$match' => ['dt.job' => ['$regex' => $expectedRegex]]]],
            $pipeline
        );
    }

    public function testANotEqualsFilterAsksForAFieldThatIsThereAndDiffers(): void
    {
        $pipeline = new TracePipelineBuilder()->make(
            data: new TraceDataFilterParameters(
                filter: [
                    new TraceDataFilterItemParameters(
                        field: 'dt.job',
                        null: null,
                        exists: null,
                        numeric: null,
                        string: new TraceDataFilterStringParameters(
                            value: 'run',
                            comp: TraceDataFilterCompStringTypeEnum::Neq
                        ),
                        boolean: null
                    ),
                ]
            )
        );

        self::assertSame(
            [
                [
                    '$match' => [
                        'dt.job' => [
                            '$exists' => true,
                            '$not'    => ['$regex' => '^run$'],
                        ],
                    ],
                ],
            ],
            $pipeline
        );
    }

    public function testEveryTagAskedForHasToBeOnTheTrace(): void
    {
        $pipeline = new TracePipelineBuilder()->make(tags: ['api', 'v2']);

        self::assertSame(
            [['$match' => ['tgs.nm' => ['$all' => ['api', 'v2']]]]],
            $pipeline
        );
    }

    /**
     * @param array<string, mixed> $expectedCondition
     */
    #[DataProvider('presenceFilterProvider')]
    public function testAPresenceFilterMatchesItsCondition(
        ?bool $null,
        ?bool $exists,
        array $expectedCondition
    ): void {
        $pipeline = (new TracePipelineBuilder())->make(
            data: new TraceDataFilterParameters(
                filter: [
                    new TraceDataFilterItemParameters(
                        field: 'dt.job',
                        null: $null,
                        exists: $exists,
                        numeric: null,
                        string: null,
                        boolean: null
                    ),
                ]
            )
        );

        self::assertSame(
            [['$match' => ['dt.job' => $expectedCondition]]],
            $pipeline
        );
    }

    /**
     * @return array<string, array{bool|null, bool|null, array<string, mixed>}>
     */
    public static function presenceFilterProvider(): array
    {
        return [
            'null'        => [true, null, ['$type' => 'null']],
            'not null'    => [false, null, ['$ne' => null]],
            'exists'      => [null, true, ['$exists' => true]],
            'not exists'  => [null, false, ['$exists' => false]],
            'exists wins' => [true, false, ['$exists' => false]],
        ];
    }

    /**
     * @return array<string, array{TraceDataFilterCompStringTypeEnum, string}>
     */
    public static function stringFilterProvider(): array
    {
        $quoted = 'App\\\\Modules\\\\ExcelExport\\\\Jobs\\\\GenerateExcelExportJob\\.run';

        return [
            'equals'   => [TraceDataFilterCompStringTypeEnum::Eq, "^$quoted$"],
            'contains' => [TraceDataFilterCompStringTypeEnum::Con, $quoted],
            'starts'   => [TraceDataFilterCompStringTypeEnum::Starts, "^$quoted"],
            'ends'     => [TraceDataFilterCompStringTypeEnum::Ends, "$quoted$"],
        ];
    }
}
