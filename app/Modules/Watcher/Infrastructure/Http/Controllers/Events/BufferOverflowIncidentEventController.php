<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers\Events;

use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\IndexIncidentEventsRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\Events\BufferOverflowIncidentEventResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class BufferOverflowIncidentEventController extends AbstractIncidentEventController
{
    #[OaListItemTypeAttribute(BufferOverflowIncidentEventResource::class)]
    public function index(string $id, IndexIncidentEventsRequest $request): AnonymousResourceCollection
    {
        return BufferOverflowIncidentEventResource::collection(
            $this->incidentEvents(WatcherTypeEnum::BufferOverflow, $id, $request->validated())
        );
    }
}
