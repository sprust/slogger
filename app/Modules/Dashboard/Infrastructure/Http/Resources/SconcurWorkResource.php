<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\SconcurWorkObject;

class SconcurWorkResource extends AbstractApiResource
{
    private int $in_process;
    private int $in_process_1_to_5s;
    private int $in_process_5_to_15s;
    private int $in_process_over_15s;
    private int $finished;
    private int $refused;
    private int $measured;
    private float $avg_ms;

    public function __construct(SconcurWorkObject $work)
    {
        parent::__construct($work);

        $this->in_process          = $work->inProcess;
        $this->in_process_1_to_5s  = $work->inProcess1to5s;
        $this->in_process_5_to_15s = $work->inProcess5to15s;
        $this->in_process_over_15s = $work->inProcessOver15s;
        $this->finished            = $work->finished;
        $this->refused             = $work->refused;
        $this->measured            = $work->measured;
        $this->avg_ms              = $work->avgMs;
    }
}
