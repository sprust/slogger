<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Resources;

trait SecretMaskTrait
{
    private const int VISIBLE_SECRET_TAIL = 4;

    protected function mask(string $secret): string
    {
        if ($secret === '') {
            return '';
        }

        return str_repeat('*', 8) . mb_substr($secret, -self::VISIBLE_SECRET_TAIL);
    }
}
