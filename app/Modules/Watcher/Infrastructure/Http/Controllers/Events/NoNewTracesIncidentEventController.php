<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers\Events;

use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\IndexIncidentEventsRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\Events\NoNewTracesIncidentEventResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class NoNewTracesIncidentEventController extends AbstractIncidentEventController
{
    #[OaListItemTypeAttribute(NoNewTracesIncidentEventResource::class)]
    public function index(string $id, IndexIncidentEventsRequest $request): AnonymousResourceCollection
    {
        return NoNewTracesIncidentEventResource::collection(
            $this->incidentEvents(WatcherTypeEnum::NoNewTraces, $id, $request->validated())
        );
    }
}
