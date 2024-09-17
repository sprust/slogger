<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

interface DeleteTraceAdminStoreActionInterface
{
    public function handle(string $id): bool;
}
