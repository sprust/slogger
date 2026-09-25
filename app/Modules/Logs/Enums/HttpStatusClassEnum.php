<?php

declare(strict_types=1);

namespace App\Modules\Logs\Enums;

enum HttpStatusClassEnum: int
{
    case Informational = 1;
    case Success = 2;
    case Redirection = 3;
    case ClientError = 4;
    case ServerError = 5;
}
