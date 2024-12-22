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

        $context = $record->context;

        if ($exception = ($context['exception'] ?? null)) {
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => explode(PHP_EOL, $exception->getTraceAsString()),
            ];
        }

        $action->handle(
            new CreateLogParameters(
                level: $record->level->getName(),
                message: $record->message,
                context: $context,
                channel: $record->channel,
                loggedAt: new Carbon($record->datetime)
            )
        );
    }
}
