<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Throwable;

class DataFormatter
{
    public static function exception(Throwable $exception): array
    {
        return [
            'message'   => $exception->getMessage(),
            'exception' => get_class($exception),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'trace'     => self::stackTrace($exception->getTrace()),
        ];
    }

    public static function model(Model $model): string
    {
        return $model::class . ':' . $model->getKey();
    }

    /**
     * @see Throwable::getTrace()
     */
    private static function stackTrace(array $stackTrace): array
    {
        return array_map(
            fn(array $item) => Arr::only($item, ['file', 'line']),
            $stackTrace
        );
    }
}
