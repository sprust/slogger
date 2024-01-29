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

        $changes = $modelInstance->getChanges();

        $data = [
            'action'  => $this->getAction($eventName),
            'model'   => $modelClass,
            'changes' => $changes ?: null,
        ];

        $this->processor->push(
            type: SLoggerTraceTypeEnum::Model,
            data: $data
        );
    }

    private function getAction(string $event): string
    {
        preg_match('/\.(.*):/', $event, $matches);

        return $matches[1];
    }

    private function shouldRecord(string $eventName): bool
    {
        return Str::is([
            '*created*',
            '*updated*',
            '*restored*',
            '*deleted*',
        ], $eventName);
    }
}
