<?php

declare(strict_types=1);

namespace App\Modules\Logs\Contracts\Actions;

use App\Modules\Logs\Parameters\CreateLogParameters;

interface CreateLogActionInterface
{
    public function handle(CreateLogParameters $parameters): void;
}
