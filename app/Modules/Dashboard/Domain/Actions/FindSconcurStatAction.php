<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Domain\Services\SconcurStatClient;
use App\Modules\Dashboard\Entities\SconcurStatObject;

readonly class FindSconcurStatAction
{
    public function __construct(
        private SconcurStatClient $client
    ) {
    }

    public function handle(): SconcurStatObject
    {
        return $this->client->find();
    }
}
