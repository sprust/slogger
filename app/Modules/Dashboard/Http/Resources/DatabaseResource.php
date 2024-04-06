<?php

namespace App\Modules\Dashboard\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Repositories\Database\Dto\DatabaseDto;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class DatabaseResource extends AbstractApiResource
{
    private string $name;
    private float $size;
    #[OaListItemTypeAttribute(DatabaseCollectionResource::class)]
    private array $collections;

    public function __construct(DatabaseDto $database)
    {
        parent::__construct($database);

        $this->name        = $database->name;
        $this->size        = $database->size;
        $this->collections = DatabaseCollectionResource::mapIntoMe($database->collections);
    }
}
