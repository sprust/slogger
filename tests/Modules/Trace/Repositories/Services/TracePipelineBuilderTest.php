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
