<?php

namespace App\Modules\Trace\Contracts\Actions\Queries;

use Illuminate\Support\Carbon;

interface FindMinLoggedAtTracesActionInterface
{
    public function handle(): ?Carbon;
}
