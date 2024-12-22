<?php

namespace App\Services\Logging\Mongodb;

use App\Models\Logs\Log;
use Illuminate\Support\Carbon;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class MongodbLogHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        $log = new Log();

        $log->level    = $record->level->getName();
        $log->message  = $record->message;
        $log->context  = $record->context;
        $log->channel  = $record->channel;
        $log->loggedAt = new Carbon($record->datetime);

        $log->save();
    }
}
