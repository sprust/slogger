<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers\Events;

use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\IndexIncidentEventsRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\Events\SlowTracesIncidentEventResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class SlowTracesIncidentEventController extends AbstractIncidentEventController
{
    #[OaListItemTypeAttribute(SlowTracesIncidentEventResource::class)]
    public function index(string $id, IndexIncidentEventsRequest $request): AnonymousResourceCollection
    {
        return SlowTracesIncidentEventResource::collection(
            $this->incidentEvents(WatcherTypeEnum::SlowTraces, $id, $request->validated())
        );
    }
}
