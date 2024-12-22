<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Mutations;

interface DeleteOldEmptyCollectionsActionInterface
{
    public function handle(): void;
}
