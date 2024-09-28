<?php

namespace RrConcurrency\Services\Drivers\Roadrunner;

class ConcurrencyJobSerializer
{
    public function serialize(ConcurrencyJob $object): string
    {
        return serialize($object);
    }

    public function unSerialize(string $payload): ConcurrencyJob
    {
        return unserialize($payload);
    }
}
