<?php

namespace App\Modules\Service\Domain\Actions\Interfaces;

use App\Modules\Service\Domain\Entities\Objects\ServiceObject;

interface FindServiceByTokenActionInterface
{
    public function handle(string $token): ?ServiceObject;
}
