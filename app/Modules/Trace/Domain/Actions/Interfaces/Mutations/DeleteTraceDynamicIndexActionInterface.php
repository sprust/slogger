<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

interface DeleteTraceDynamicIndexActionInterface
{
    public function handle(string $id): bool;
}
