<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Mutations;

interface DeleteTraceTreesByTraceIdsActionInterface
{
    /**
     * @param string[] $ids
     */
    public function handle(array $ids): void;
}
