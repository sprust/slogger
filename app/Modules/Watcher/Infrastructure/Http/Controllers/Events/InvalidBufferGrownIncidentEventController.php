<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers\Events;

use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\IndexIncidentEventsRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\Events\InvalidBufferGrownIncidentEventResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class InvalidBufferGrownIncidentEventController extends AbstractIncidentEventController
{
    #[OaListItemTypeAttribute(InvalidBufferGrownIncidentEventResource::class)]
    public function index(string $id, IndexIncidentEventsRequest $request): AnonymousResourceCollection
    {
        return InvalidBufferGrownIncidentEventResource::collection(
            $this->incidentEvents(WatcherTypeEnum::InvalidBufferGrown, $id, $request->validated())
        );
    }
}
