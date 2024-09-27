<?php

namespace RrConcurrency\Services;

class JobsPayloadSerializer
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
