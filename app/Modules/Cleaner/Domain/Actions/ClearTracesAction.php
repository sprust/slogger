<?php

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Domain\Events\ProcessAlreadyActiveEvent;
use App\Modules\Cleaner\Repositories\Dto\SettingDto;
use App\Modules\Cleaner\Repositories\Interfaces\ProcessRepositoryInterface;
use App\Modules\Cleaner\Repositories\Interfaces\SettingRepositoryInterface;
use App\Modules\Common\EventsDispatcher;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTracesByTraceIdsAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceTreesByTraceIdsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceIdsAction;
use App\Modules\Trace\Domain\Entities\Parameters\FindTraceIdsParameters;
use Illuminate\Support\Arr;

readonly class ClearTracesAction
{
    private int $daysLifetimeOfProcessData;
    private int $countInDeletionBatch;

    public function __construct(
        private EventsDispatcher $eventsDispatcher,
        private SettingRepositoryInterface $settingRepository,
        private ProcessRepositoryInterface $processRepository,
        private FindTraceIdsAction $findTraceIdsAction,
        private DeleteTracesByTraceIdsAction $deleteTracesByTraceIdsAction,
        private DeleteTraceTreesByTraceIdsAction $deleteTraceTreesByTraceIdsAction
    ) {
        $this->countInDeletionBatch      = 1000;
        $this->daysLifetimeOfProcessData = 5;
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

        foreach ($settings as $setting) {
            $loggedAtTo    = now()->subDays($setting->daysLifetime);
            $type          = $setting->type;
            $excludedTypes = is_null($type) ? $customizedTypes : [];

            $activeProcess = $this->processRepository->findFirstBySettingId(
                settingId: $setting->id,
                clearedAtIsNotNull: true
            );

            if ($activeProcess) {
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

            $clearedCount = 0;

            while (true) {
                $traceIds = $this->findTraceIdsAction->handle(
                    new FindTraceIdsParameters(
                        limit: $this->countInDeletionBatch,
                        loggedAtTo: $loggedAtTo,
                        type: $type,
                        excludedTypes: $excludedTypes
                    )
                );

                if (empty($traceIds)) {
                    break;
                }

                $this->deleteTracesByTraceIdsAction->handle(
                    ids: $traceIds
                );

                $this->deleteTraceTreesByTraceIdsAction->handle(
                    ids: $traceIds
                );

                $clearedCount += count($traceIds);
            }

            $this->processRepository->update(
                processId: $process->id,
                clearedCount: $clearedCount,
                clearedAt: now()
            );
        }

        $this->deleteOldProcesses();
    }

    private function deleteOldProcesses(): void
    {
        $this->processRepository->delete(
            to: now()->subDays($this->daysLifetimeOfProcessData)
        );
    }
}
