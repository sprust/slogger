<?php

declare(strict_types=1);

namespace Tests\Services\OpenApi\Fixtures;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use stdClass;

class UnionResource extends AbstractApiResource
{
    private TextResource|CountResource $payload;
    private stdClass $empty;
}
