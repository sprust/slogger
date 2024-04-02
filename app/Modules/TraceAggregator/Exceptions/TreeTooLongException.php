<?php

namespace App\Modules\TraceAggregator\Exceptions;

use Exception;

class TreeTooLongException extends Exception
{
    public function __construct(int $limit, int $current)
    {
        parent::__construct("The tree is too long [more $current at $limit] for to view");
    }
}
