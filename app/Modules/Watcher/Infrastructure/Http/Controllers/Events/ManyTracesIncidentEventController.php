<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers\Events;

use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\IndexIncidentEventsRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\Events\ManyTracesIncidentEventResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ManyTracesIncidentEventController extends AbstractIncidentEventController
{
    #[OaListItemTypeAttribute(ManyTracesIncidentEventResource::class)]
    public function index(string $id, IndexIncidentEventsRequest $request): AnonymousResourceCollection
    {
        return ManyTracesIncidentEventResource::collection(
            $this->incidentEvents(WatcherTypeEnum::ManyTraces, $id, $request->validated())
        );
    }
}
