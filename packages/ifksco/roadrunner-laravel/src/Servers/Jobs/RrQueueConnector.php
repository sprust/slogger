<?php

namespace RoadRunner\Servers\Jobs;

use Illuminate\Queue\Connectors\ConnectorInterface;

class RrQueueConnector implements ConnectorInterface
{
    public function connect(array $config)
    {
        return app(RrQueueConnection::class);
    }
}
