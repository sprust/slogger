<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Contracts\Actions\MakeMetricIndicatorsActionInterface;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;

readonly class MakeMetricIndicatorsAction implements MakeMetricIndicatorsActionInterface
{
    /**
     * @return TraceMetricFieldEnum[]
     */
    public function handle(): array
    {
        return TraceMetricFieldEnum::cases();
    }
}
