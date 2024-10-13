<?php

namespace App\Modules\Trace\Contracts\Actions\Mutations;

interface DeleteTraceAdminStoreActionInterface
{
    public function handle(string $id): bool;
}
