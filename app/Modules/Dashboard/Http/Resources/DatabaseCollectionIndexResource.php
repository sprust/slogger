<?php

namespace App\Modules\Dashboard\Http\Resources;

use App\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Dto\Objects\Database\DatabaseCollectionIndexObject;

class DatabaseCollectionIndexResource extends AbstractApiResource
{
    private string $name;
    private float $size;
    private int $usage;

    public function __construct(DatabaseCollectionIndexObject $collection)
    {
        parent::__construct($collection);

        $this->name  = $collection->name;
        $this->size  = $collection->size;
        $this->usage = $collection->usage;
    }
}
