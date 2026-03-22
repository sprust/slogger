<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Mutations;

use App\Modules\Trace\Entities\Trace\DeletedTracesObject;
use Illuminate\Support\Carbon;

interface DeleteCollectionsActionInterface
{
    public function handle(Carbon $loggedAtTo): DeletedTracesObject;
}
