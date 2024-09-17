<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use Illuminate\Support\Carbon;

interface FindMinLoggedAtTracesActionInterface
{
    public function handle(): ?Carbon;
}
