<?php

namespace App\Modules\Dashboard\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Domain\Entities\Objects\DatabaseStatObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class DatabaseResource extends AbstractApiResource
{
    private string $name;
    private float $size;
    #[OaListItemTypeAttribute(DatabaseCollectionResource::class)]
    private array $collections;

    public function __construct(DatabaseStatObject $database)
    {
        parent::__construct($database);

        $this->name        = $database->name;
        $this->size        = $database->size;
        $this->collections = DatabaseCollectionResource::mapIntoMe($database->collections);
    }
}
