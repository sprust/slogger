<?php

namespace App\Modules\Trace\Contracts\Actions\Mutations;

interface DeleteTraceDynamicIndexActionInterface
{
    public function handle(string $id): bool;
}
