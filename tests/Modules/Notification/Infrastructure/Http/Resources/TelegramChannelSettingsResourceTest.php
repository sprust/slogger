<?php

namespace Tests\Modules\Notification\Infrastructure\Http\Resources;

use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Infrastructure\Http\Resources\TelegramChannelSettingsResource;
use PHPUnit\Framework\TestCase;

class TelegramChannelSettingsResourceTest extends TestCase
{
    public function testTheTokenGoesOutAsAMask(): void
    {
        $data = new TelegramChannelSettingsResource(
            id: 7,
            resource: new TelegramSettingsObject(
                botToken: '000000000:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAfake',
                chatId: '@ops'
            )
        )->toArray();

        $this->assertSame(7, $data['id']);
        $this->assertSame('********fake', $data['bot_token_mask']);
        $this->assertSame('@ops', $data['chat_id']);
    }

    public function testAnUnsetTokenHasNoMask(): void
    {
        $data = new TelegramChannelSettingsResource(7, new TelegramSettingsObject())->toArray();

        $this->assertSame('', $data['bot_token_mask']);
    }
}
