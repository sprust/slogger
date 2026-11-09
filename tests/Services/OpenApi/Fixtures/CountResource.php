<?php

declare(strict_types=1);

namespace Tests\Services\OpenApi\Fixtures;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use Ifksco\OpenApiGenerator\Attributes\OaResponseStatusCodeAttribute;

#[OaResponseStatusCodeAttribute('201')]
class CountResource extends AbstractApiResource
{
    private int $count = 1;
}
