<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Mutations;

interface DeleteTraceAdminStoreActionInterface
{
    public function handle(string $id): bool;
}
