<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers\Events;

use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\IndexIncidentEventsRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\Events\LogErrorsIncidentEventResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class LogErrorsIncidentEventController extends AbstractIncidentEventController
{
    #[OaListItemTypeAttribute(LogErrorsIncidentEventResource::class)]
    public function index(string $id, IndexIncidentEventsRequest $request): AnonymousResourceCollection
    {
        return LogErrorsIncidentEventResource::collection(
            $this->incidentEvents(WatcherTypeEnum::LogErrors, $id, $request->validated())
        );
    }
}
