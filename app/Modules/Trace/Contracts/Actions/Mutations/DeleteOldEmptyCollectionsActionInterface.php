<?php

namespace App\Modules\Trace\Contracts\Actions\Mutations;

interface DeleteOldEmptyCollectionsActionInterface
{
    public function handle(): void;
}
