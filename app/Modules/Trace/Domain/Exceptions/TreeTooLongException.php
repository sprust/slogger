<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Exceptions;

use Exception;

class TreeTooLongException extends Exception
{
    public function __construct(int $limit, int $current)
    {
        parent::__construct("The tree is too long [$current at $limit] for to view");
    }
}
