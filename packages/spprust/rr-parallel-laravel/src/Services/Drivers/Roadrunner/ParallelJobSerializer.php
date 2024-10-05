<?php

namespace RrParallel\Services\Drivers\Roadrunner;

class ParallelJobSerializer
{
    public function serialize(ParallelJob $object): string
    {
        return serialize($object);
    }

    public function unSerialize(string $payload): ParallelJob
    {
        return unserialize($payload);
    }
}
