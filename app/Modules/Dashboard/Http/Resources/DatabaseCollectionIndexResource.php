<?php

namespace App\Modules\Dashboard\Http\Resources;

use App\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Repositories\Database\Dto\DatabaseCollectionIndexDto;

class DatabaseCollectionIndexResource extends AbstractApiResource
{
    private string $name;
    private float $size;
    private int $usage;

    public function __construct(DatabaseCollectionIndexDto $collection)
    {
        parent::__construct($collection);

        $this->name  = $collection->name;
        $this->size  = $collection->size;
        $this->usage = $collection->usage;
    }
}
