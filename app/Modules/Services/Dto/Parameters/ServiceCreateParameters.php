<?php

namespace App\Modules\Services\Dto\Parameters;

use Illuminate\Support\Str;

readonly class ServiceCreateParameters
{
    public string $uniqueKey;

    public function __construct(public string $name)
    {
        $this->uniqueKey = Str::slug($this->name);
    }
}
