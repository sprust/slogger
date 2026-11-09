<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Integration;

use Tests\Modules\BaseModuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Modules\Trace\Domain\UseCases\FindTraceDetailByAdminUseCase;
use Tests\Factories\Entities\TraceFactory;
use Tests\Factories\Entities\ServiceFactory;

class TraceDetailDatabaseTest extends BaseModuleTestCase
{
    #[DataProvider('dataProvider')]
    public function test(
        bool $withParent
    ): void {
        $serviceId     = $this->appMake(ServiceFactory::class)->create();
        $traceFactory  = $this->appMake(TraceFactory::class);
        $parentTraceId = $withParent ? $traceFactory->create(
            serviceId: $serviceId,
        ) : null;
        $traceId = $traceFactory->create(
            serviceId: $serviceId,
            parentTraceId: $parentTraceId,
            type: 'request',
            status: 'success',
        );
        $traceFactory->create();

        $trace = $this->appMake(FindTraceDetailByAdminUseCase::class)->handle($traceId);

        self::assertSame(
            expected: $traceId,
            actual: $trace->traceId
        );
        self::assertSame(
            expected: $parentTraceId,
            actual: $trace->parentTraceId
        );
        self::assertSame(
            expected: $serviceId,
            actual: $trace->service?->id
        );
        self::assertSame(
            expected: 'success',
            actual: $trace->status
        );
    }

    /** @return array<string, array{bool}> */
    public static function dataProvider(): array
    {
        return [
            'root trace'  => [false],
            'child trace' => [true],
        ];
    }
}
