<?php

namespace App\Modules\Tools\Framework\Services;

use App\Modules\Tools\Framework\Services\Objects\ToolLinksObject;

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
