<?php

namespace SLoggerLaravel\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Throwable;

class SLoggerDataFormatter
{
    public static function exception(Throwable $exception): array
    {
        return [
            'message'   => $exception->getMessage(),
            'exception' => get_class($exception),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'trace'     => array_map(
                fn(array $item) => Arr::only($item, ['file', 'line']),
                $exception->getTrace()
            ),
        ];
    }

    public static function model(Model $model): string
    {
        return $model::class . ':' . $model->getKey();
    }
}
