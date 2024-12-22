<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions;

use App\Modules\Trace\Enums\TraceMetricFieldEnum;

interface MakeMetricIndicatorsActionInterface
{
    /**
     * @return TraceMetricFieldEnum[]
     */
    public function handle(): array;
}
