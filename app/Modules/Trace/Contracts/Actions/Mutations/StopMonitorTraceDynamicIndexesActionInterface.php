<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Mutations;

interface StopMonitorTraceDynamicIndexesActionInterface
{
    public function handle(): void;
}
