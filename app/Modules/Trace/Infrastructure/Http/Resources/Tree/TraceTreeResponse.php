<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractStreamedApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeResultObject;

class TraceTreeResponse extends AbstractStreamedApiResource
{
    public function __construct(TraceTreeResultObject $resource)
    {
        parent::__construct(
            callback: static function () use ($resource) {
                echo '{"data":{"state":' . new TraceTreeStateResource($resource->state)->toJson() . ',"items":';

                if ($resource->items === null) {
                    echo 'null}}';

                    return;
                }

                echo "[";

                $first = true;

                foreach ($resource->items as $item) {
                    $comma = $first ? '' : ',';

                    $first = false;

                    echo $comma . new TraceTreeResource($item)->toJson();

                    flush();
                }

                echo "]}}";
            },
        );
    }
}
