<?php

namespace RoadRunner\Servers\Jobs;

class RrJobsPayloadSerializer
{
    public function serialize(object $object): string
    {
        return serialize($object);
    }

    public function unSerialize(string $payload): mixed
    {
        return unserialize($payload);
    }
}
