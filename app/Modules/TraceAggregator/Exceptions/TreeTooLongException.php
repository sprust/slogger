<?php

namespace App\Modules\TraceAggregator\Exceptions;

use Exception;

class TreeTooLongException extends Exception
{
    public function __construct(int $limit)
    {
        parent::__construct("The tree is too long [more $limit] for to view");
    }
}
