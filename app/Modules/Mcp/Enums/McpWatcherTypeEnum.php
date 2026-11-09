<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Enums;

enum McpWatcherTypeEnum: string
{
    case BufferOverflow     = 'bufferOverflow';

    case InvalidBufferGrown = 'invalidBufferGrown';

    case NoNewTraces        = 'noNewTraces';

    case ManyTraces         = 'manyTraces';

    case SlowTraces         = 'slowTraces';

    case LogErrors          = 'logErrors';

    case ReceiverErrors     = 'receiverErrors';
}
