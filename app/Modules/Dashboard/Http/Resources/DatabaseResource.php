<?php

namespace App\Modules\Dashboard\Http\Resources;

use App\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Dto\Objects\Database\DatabaseObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class DatabaseResource extends AbstractApiResource
{
    private string $name;
    private float $size;
    #[OaListItemTypeAttribute(DatabaseCollectionResource::class)]
    private array $collections;

    public function __construct(DatabaseObject $database)
    {
        parent::__construct($database);

        $this->name        = $database->name;
        $this->size        = $database->size;
        $this->collections = DatabaseCollectionResource::mapIntoMe($database->collections);
    }
}
