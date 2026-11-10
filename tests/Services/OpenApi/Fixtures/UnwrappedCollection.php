<?php

declare(strict_types=1);

namespace Tests\Services\OpenApi\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UnwrappedCollection extends ResourceCollection
{
    public static $wrap = null;
}
