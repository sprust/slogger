<?php

declare(strict_types=1);

namespace Tests\Services\OpenApi\Fixtures;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;

class TextResource extends AbstractApiResource
{
    private string $text = 'text';
}
