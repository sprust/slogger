<?php

namespace App\Modules\Trace\Domain\Exceptions;

use Exception;

class TraceDynamicIndexInProcessException extends Exception
{
    private ?float $progress = null;

    public function getProgress(): ?float
    {
        return $this->progress;
    }

    public function setProgress(?float $progress): static
    {
        $this->progress = $progress;

        return $this;
    }
}
