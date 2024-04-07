<?php

namespace App\Modules\Dashboard\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Domain\Entities\Objects\DatabaseCollectionIndexStatObject;

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
