<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;

class SLoggerModelWatcher extends AbstractSLoggerWatcher
{
    public function register(): void
    {
        $this->listenEvent('eloquent.*', [$this, 'handleAction']);
    }

    public function handleAction(string $eventName, array $eventData): void
    {
        if (!$this->shouldRecord($eventName)) {
            return;
        }

        /** @var Model $modelInstance */
        $modelInstance = $eventData['model'] ?? $eventData[0];

        $modelClass = $modelInstance::class;

        $data = [
            'action'  => $this->getAction($eventName),
            'model'   => $modelClass,
            'key'     => $modelInstance->getKey(),
            'changes' => $this->prepareChanges($modelInstance),
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Model,
            data: $data
        );
    }

    protected function getAction(string $event): string
    {
        preg_match('/\.(.*):/', $event, $matches);

        return $matches[1];
    }

    protected function shouldRecord(string $eventName): bool
    {
        return Str::is([
            '*created*',
            '*updated*',
            '*restored*',
            '*deleted*',
            //'*retrieved*', // list
        ], $eventName);
    }

    protected function prepareChanges(Model $modelInstance): ?array
    {
        return $modelInstance->getChanges() ?: null;
    }
}
