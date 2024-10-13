<?php

namespace App\Modules\Service\Contracts\Actions;

use App\Modules\Service\Entities\ServiceObject;

interface FindServiceByTokenActionInterface
{
    public function handle(string $token): ?ServiceObject;
}
