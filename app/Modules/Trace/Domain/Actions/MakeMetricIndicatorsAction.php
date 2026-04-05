<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Enums\TraceMetricFieldEnum;

readonly class MakeMetricIndicatorsAction
{
    /**
     * @return TraceMetricFieldEnum[]
     */
    public function handle(): array
    {
        return TraceMetricFieldEnum::cases();
    }
}
