<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Logs\Entities\Entry\LogEntriesPageObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class LogEntriesPageResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(LogEntryResource::class)]
    private array $items;
    #[OaListItemTypeAttribute(LogLevelCountResource::class)]
    private array $level_counts;
    private int $total;
    private int $scanned;
    private bool $indexing;
    private int $indexed_bytes;
    private int $total_bytes;
    #[OaListItemTypeAttribute('string')]
    private array $restarted_files;
    #[OaListItemTypeAttribute('string')]
    private array $missing_files;
    private ?string $older_cursor;
    private ?string $newer_cursor;

    public function __construct(LogEntriesPageObject $resource)
    {
        parent::__construct($resource);

        $this->items = LogEntryResource::mapIntoMe($resource->items);

        $this->level_counts    = LogLevelCountResource::mapIntoMe($resource->levelCounts);
        $this->total           = $resource->total;
        $this->scanned         = $resource->scanned;
        $this->indexing        = $resource->indexing;
        $this->indexed_bytes   = $resource->indexedBytes;
        $this->total_bytes     = $resource->totalBytes;
        $this->restarted_files = $resource->restartedFileIds;
        $this->missing_files   = $resource->missingFileIds;
        $this->older_cursor    = $resource->olderCursor;
        $this->newer_cursor    = $resource->newerCursor;
    }
}
