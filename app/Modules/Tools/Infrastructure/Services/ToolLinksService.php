<?php

namespace App\Modules\Tools\Infrastructure\Services;

use App\Modules\Tools\Infrastructure\Services\Objects\ToolLinksObject;

class ToolLinksService
{
    /**
     * @return ToolLinksObject[]
     */
    public function get(): array
    {
        return [
            new ToolLinksObject(
                'RabbitMQ',
                config('tools.rabbitmq.admin-url')
            ),
        ];
    }
}
