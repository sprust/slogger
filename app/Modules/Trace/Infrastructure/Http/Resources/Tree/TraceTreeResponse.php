<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractStreamedApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawIterator;

class TraceTreeResponse extends AbstractStreamedApiResource
{
    public function __construct(TraceTreeRawIterator $iterator)
    {
        parent::__construct(
            callback: static function () use ($iterator) {
                echo "[";

                $first = true;

                while (true) {
                    $iterator->next();

                    $item = $iterator->current();

                    if (is_null($item)) {
                        break;
                    }

                    $comma = $first ? '' : ',';

                    $first = false;

                    echo $comma . (new TraceTreeResource($item))->toJson();

                    flush();
                }

                echo "]";
            },
        );
    }
}
