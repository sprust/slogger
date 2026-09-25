<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Logs\Entities\Entry\LogEntryViewObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Support\Carbon;

class LogEntryResource extends AbstractApiResource
{
    private string $file_id;
    private string $type;
    private int $entry_no;
    private string $logged_at;
    private string $level;
    private string $message;
    private ?string $context;
    #[OaListItemTypeAttribute(LogEntryFieldResource::class)]
    private array $fields;
    private string $text;
    private bool $truncated;

    public function __construct(LogEntryViewObject $resource)
    {
        parent::__construct($resource);

        $this->file_id   = $resource->fileId;
        $this->type      = $resource->type->value;
        $this->entry_no  = $resource->entryNo;
        $this->logged_at = Carbon::createFromTimestamp($resource->loggedAt)->toDateTimeString();
        $this->level     = $resource->levelKey;
        $this->message   = $resource->details->message;
        $this->context   = $resource->details->context;
        $this->fields    = LogEntryFieldResource::mapIntoMe($resource->details->fields);
        $this->text      = mb_scrub($resource->text, 'UTF-8');
        $this->truncated = $resource->truncated;
    }
}
