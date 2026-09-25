<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Enums\LogTypeEnum;

readonly class LogFormatRegistry
{
    public function __construct(
        private LaravelLogFormat $laravelLogFormat,
        private NginxAccessLogFormat $nginxAccessLogFormat,
        private NginxErrorLogFormat $nginxErrorLogFormat,
        private ReceiverLogFormat $receiverLogFormat
    ) {
    }

    public function get(LogTypeEnum $type): LogFormatInterface
    {
        return match ($type) {
            LogTypeEnum::Laravel     => $this->laravelLogFormat,
            LogTypeEnum::NginxAccess => $this->nginxAccessLogFormat,
            LogTypeEnum::NginxError  => $this->nginxErrorLogFormat,
            LogTypeEnum::Receiver    => $this->receiverLogFormat,
        };
    }
}
