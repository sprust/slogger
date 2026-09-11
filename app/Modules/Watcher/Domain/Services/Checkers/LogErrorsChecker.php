<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Logs\Domain\Actions\FindLogErrorStatAction;
use App\Modules\Watcher\Domain\Services\WatcherCountWindow;
use App\Modules\Watcher\Entities\Events\LogErrorsEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\LogErrorsEventPayloadObject;
use App\Modules\Watcher\Entities\Events\LogErrorsEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use App\Modules\Watcher\Entities\Settings\LogErrorsSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;

readonly class LogErrorsChecker implements WatcherCheckerInterface
{
    public const int MAX_MESSAGE_LENGTH = 500;

    public function __construct(
        private FindLogErrorStatAction $findLogErrorStatAction,
        private WatcherCountWindow $countWindow
    ) {
    }

    public function check(WatcherObject $watcher, WatcherCheckContextObject $context): ?WatcherEventPayloadInterface
    {
        $settings = $watcher->settings;

        if (!$settings instanceof LogErrorsSettingsObject) {
            return null;
        }

        $since = $this->countWindow->start($watcher, $context->now);

        $stat = $this->findLogErrorStatAction->handle($since, $context->now);

        if ($stat->count < $settings->threshold) {
            return null;
        }

        return new LogErrorsEventPayloadObject(
            settings: new LogErrorsEventSettingsObject(threshold: $settings->threshold),
            measured: new LogErrorsEventMeasuredObject(
                errorCount: $stat->count,
                since: $since->toDateTimeString(),
                lastMessage: mb_strimwidth($stat->lastMessage ?? '', 0, self::MAX_MESSAGE_LENGTH, '…')
            )
        );
    }
}
