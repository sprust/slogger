<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;

class TelegramChannelSettingsResource extends AbstractApiResource
{
    use SecretMaskTrait;

    private int $id;
    private string $bot_token_mask;
    private string $chat_id;

    public function __construct(int $id, TelegramSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->id             = $id;
        $this->bot_token_mask = $this->mask($resource->botToken);
        $this->chat_id        = $resource->chatId;
    }
}
