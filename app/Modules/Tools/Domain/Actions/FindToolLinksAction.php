<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain\Actions;

use App\Modules\Tools\Entities\ToolLinksObject;

readonly class FindToolLinksAction
{
    /**
     * @return ToolLinksObject[]
     */
    public function handle(): array
    {
        return [
            new ToolLinksObject(
                name: 'RabbitMQ',
                url: config(
                    key: 'tools.rabbitmq.admin-url',
                ),
            ),
        ];
    }
}
