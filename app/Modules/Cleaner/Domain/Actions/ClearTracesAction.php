<?php

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Domain\Actions\Interfaces\ClearTracesActionInterface;
use App\Modules\Cleaner\Domain\Events\ProcessAlreadyActiveEvent;
use App\Modules\Cleaner\Repositories\Dto\SettingDto;
use App\Modules\Cleaner\Repositories\Interfaces\ProcessRepositoryInterface;
use App\Modules\Cleaner\Repositories\Interfaces\SettingRepositoryInterface;
use App\Modules\Common\EventsDispatcher;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\ClearTracesActionInterface as TraceClearTracesActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\DeleteTracesActionInterface;
use App\Modules\Trace\Domain\Entities\Parameters\ClearTracesParameters;
use App\Modules\Trace\Domain\Entities\Parameters\DeleteTracesParameters;
use Illuminate\Support\Arr;

readonly class ClearTracesAction implements ClearTracesActionInterface
{
    public function __construct(
        private EventsDispatcher $eventsDispatcher,
        private SettingRepositoryInterface $settingRepository,
        private ProcessRepositoryInterface $processRepository,
        private TraceClearTracesActionInterface $clearTracesAction,
        private DeleteTracesActionInterface $deleteTracesAction,
    ) {
    }

    public function handle(): void
    {
        /** @var SettingDto[] $settings */
        $settings = Arr::sort(
            $this->settingRepository->find(deleted: false),
            fn(SettingDto $settingDto) => is_null($settingDto->type) ? 1 : 0
        );

        if (empty($settings)) {
            return;
        }

        $customizedTypes = array_unique(
            array_filter(
                array_map(
                    fn(SettingDto $settingDto) => $settingDto->type,
                    $settings
                )
            )
        );

        $now = now('UTC');

        foreach ($settings as $setting) {
            $type          = $setting->type;
            $loggedAtTo    = $now->clone()->subDays($setting->daysLifetime);
            $excludedTypes = is_null($type) ? $customizedTypes : [];

            $activeProcess = $this->processRepository->findFirstBySettingId(
                settingId: $setting->id,
                clearedAtIsNull: true
            );

            if (!empty($activeProcess)) {
                $this->eventsDispatcher->dispatch(
                    new ProcessAlreadyActiveEvent($setting->id)
                );

                continue;
            }

            $process = $this->processRepository->create(
                settingId: $setting->id,
                clearedCount: 0,
                clearedAt: null
            );

            if ($setting->onlyData) {
                $clearedCount = $this->clearTracesAction->handle(
                    new ClearTracesParameters(
                        loggedAtTo: $loggedAtTo,
                        type: $type,
                        excludedTypes: $excludedTypes
                    )
                );
            } else {
                $clearedCount = $this->deleteTracesAction->handle(
                    new DeleteTracesParameters(
                        loggedAtTo: $loggedAtTo,
                        type: $type,
                        excludedTypes: $excludedTypes
                    )
                );
            }

            if ($clearedCount === 0) {
                $this->processRepository->deleteByProcessId(
                    processId: $process->id
                );
            } else {
                $this->processRepository->update(
                    processId: $process->id,
                    clearedCount: $clearedCount,
                    clearedAt: now()
                );
            }
        }
    }
}
