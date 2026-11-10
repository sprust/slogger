<?php

declare(strict_types=1);

namespace Tests\Services\OpenApi\Fixtures;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use stdClass;

class UnionResource extends AbstractApiResource
{
    private TextResource|CountResource $payload;
    private stdClass $empty;

    public function __construct(TextResource|CountResource $payload)
    {
        parent::__construct(null);
        $this->payload = $payload;
        $this->empty   = new stdClass();
    }

    /** @return array{payload: TextResource|CountResource, empty: stdClass} */
    public function toArray($request = null): array
    {
        return ['payload' => $this->payload, 'empty' => $this->empty];
    }
}
