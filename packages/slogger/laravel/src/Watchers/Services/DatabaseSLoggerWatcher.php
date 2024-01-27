<?php

namespace SLoggerLaravel\Watchers\Services;

use Illuminate\Database\Events\QueryExecuted;
use SLoggerLaravel\Enums\SLoggerTraceTypeEnum;
use SLoggerLaravel\Helpers\FetchesStackTraceTrait;
use SLoggerLaravel\Helpers\TraceIdHelper;
use SLoggerLaravel\Watchers\AbstractSLoggerWatcher;
use Throwable;

class DatabaseSLoggerWatcher extends AbstractSLoggerWatcher
{
    use FetchesStackTraceTrait;

    public function register(): void
    {
        $this->app['events']->listen(QueryExecuted::class, [$this, 'handle']);
    }

    public function handle(QueryExecuted $event): void
    {
        if (!$this->processor->isActive()) {
            return;
        }

        $caller = $this->getCallerFromStackTrace();

        try {
            $sql = $this->replaceSqlBindings($event);
        } catch (Throwable) {
            $sql = $event->sql;
        }

        $data = [
            'connection' => $event->connectionName,
            'bindings'   => [],
            'sql'        => $sql,
            'duration'   => TraceIdHelper::roundDuration($event->time / 1000),
            'file'       => $caller['file'] ?? '?',
            'line'       => $caller['line'] ?? '?',
            'hash'       => md5($event->sql),
        ];

        $this->dispatchTrace(
            type: SLoggerTraceTypeEnum::Database,
            tags: [],
            data: $data,
            loggedAt: now()
        );
    }

    protected function formatBindings(QueryExecuted $event): array
    {
        return $event->connection->prepareBindings($event->bindings);
    }

    /**
     * @throws Throwable
     */
    public function replaceSqlBindings(QueryExecuted $event): string
    {
        $sql = $event->sql;

        foreach ($this->formatBindings($event) as $key => $binding) {
            $regex = is_numeric($key)
                ? "/\?(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/"
                : "/:{$key}(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/";

            if ($binding === null) {
                $binding = 'null';
            } elseif (!is_int($binding) && !is_float($binding)) {
                $binding = $this->quoteStringBinding($event, $binding);
            }

            $sql = preg_replace($regex, $binding, $sql, 1);
        }

        return $sql;
    }

    /**
     * @throws Throwable
     */
    protected function quoteStringBinding(QueryExecuted $event, string $binding): false|string
    {
        try {
            return $event->connection->getPdo()->quote($binding);
        } catch (\PDOException $e) {
            throw_if('IM001' !== $e->getCode(), $e);
        }

        // Fallback when PDO::quote function is missing...
        $binding = strtr($binding, [
            chr(26) => '\\Z',
            chr(8)  => '\\b',
            '"'     => '\"',
            "'"     => "\'",
            '\\'    => '\\\\',
        ]);

        return "'" . $binding . "'";
    }
}
