<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

interface FreshTraceTimestampsActionInterface
{
    public function handle(): void;
}
