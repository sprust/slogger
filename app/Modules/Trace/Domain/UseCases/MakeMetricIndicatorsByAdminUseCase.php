<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Domain\Actions\MakeMetricIndicatorsAction;

readonly class MakeMetricIndicatorsByAdminUseCase
{
    public function __construct(private MakeMetricIndicatorsAction $makeMetricIndicatorsAction)
    {
    }

    /**
     * @return TraceMetricFieldEnum[]
     */
    public function handle(): array
    {
        return $this->makeMetricIndicatorsAction->handle();
    }
}
