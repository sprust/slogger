<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Notification\Entities\Settings\SlackSettingsObject;

class SlackChannelSettingsResource extends AbstractApiResource
{
    use SecretMaskTrait;

    private int $id;
    private string $webhook_url_mask;

    public function __construct(int $id, SlackSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->id               = $id;
        $this->webhook_url_mask = $this->mask($resource->webhookUrl);
    }
}
