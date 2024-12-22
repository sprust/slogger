<?php

namespace App\Services\Logging\Mongodb;

use App\Modules\Logs\Domain\Actions\CreateLogAction;
use App\Modules\Logs\Parameters\CreateLogParameters;
use Illuminate\Support\Carbon;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class MongodbLogHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        /** @var CreateLogAction $action */
        $action = app(CreateLogAction::class);

        $action->handle(
            new CreateLogParameters(
                level: $record->level->getName(),
                message: $record->message,
                context: $record->context,
                channel: $record->channel,
                loggedAt: new Carbon($record->datetime)
            )
        );
    }
}
