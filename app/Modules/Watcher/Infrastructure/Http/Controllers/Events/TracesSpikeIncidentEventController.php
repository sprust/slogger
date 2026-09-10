<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers\Events;

use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\IndexIncidentEventsRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\Events\TracesSpikeIncidentEventResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TracesSpikeIncidentEventController extends AbstractIncidentEventController
{
    #[OaListItemTypeAttribute(TracesSpikeIncidentEventResource::class)]
    public function index(string $id, IndexIncidentEventsRequest $request): AnonymousResourceCollection
    {
        return TracesSpikeIncidentEventResource::collection(
            $this->incidentEvents(WatcherTypeEnum::TracesSpike, $id, $request->validated())
        );
    }
}
