<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Mutations;

interface FreshTraceTreesActionInterface
{
    public function handle(): void;
}
