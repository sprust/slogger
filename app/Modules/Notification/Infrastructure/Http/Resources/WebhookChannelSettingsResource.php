<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Notification\Entities\Settings\WebhookSettingsObject;

class WebhookChannelSettingsResource extends AbstractApiResource
{
    use SecretMaskTrait;

    private int $id;
    private string $url;
    private string $token_mask;

    public function __construct(int $id, WebhookSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->id         = $id;
        $this->url        = $resource->url;
        $this->token_mask = $this->mask($resource->token);
    }
}
