<?php

namespace App\Models;

abstract class AbstractTraceModel extends AbstractMongoModel
{
    abstract function getCollectionName(): string;

    protected $connection = 'mongodb.traces';

    public function getTable()
    {
        return $this->getCollectionName();
    }
}
