<?php

namespace RrConcurrency\Services\Drivers\Roadrunner;

use Spiral\Goridge\RPC\RPC;
use Spiral\Goridge\RPC\RPCInterface;

class RpcFactory
{
    private RPCInterface $rpc;

    public function __construct(string $host, int $port)
    {
        $this->rpc = RPC::create(
            sprintf('tcp://%s:%s', $host, $port)
        );
    }

    public function get(): RPCInterface
    {
        return $this->rpc;
    }
}
