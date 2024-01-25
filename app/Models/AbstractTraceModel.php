<?php

namespace App\Models;

abstract class AbstractTraceModel extends AbstractMongoModel
{
    protected $connection = 'mongodb.traces';
}
