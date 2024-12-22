<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\DatabaseCollectionIndexStatObject;

class DatabaseCollectionIndexResource extends AbstractApiResource
{
    private string $name;
    private float $size;
    private int $usage;

    public function __construct(DatabaseCollectionIndexStatObject $collection)
    {
        parent::__construct($collection);

        $this->name  = $collection->name;
        $this->size  = $collection->size;
        $this->usage = $collection->usage;
    }
}
