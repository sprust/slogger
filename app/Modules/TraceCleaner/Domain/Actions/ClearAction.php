<?php

namespace App\Modules\TraceCleaner\Domain\Actions;

use App\Modules\TraceCleaner\Domain\Events\ProcessAlreadyActiveEvent;
use App\Modules\TraceCleaner\Repositories\Dto\SettingDto;
use App\Modules\TraceCleaner\Repositories\Interfaces\ProcessRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Interfaces\SettingRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Interfaces\TraceTreeRepositoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;

readonly class ClearAction
{
    private int $daysLifetimeOfProcessData;
    private int $countInDeletionBatch;

    public function __construct(
        private Application $app,
        private SettingRepositoryInterface $settingRepository,
        private ProcessRepositoryInterface $processRepository,
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository
    ) {
        $this->countInDeletionBatch      = 1000;
        $this->daysLifetimeOfProcessData = 5;
    }

    public function handle(): void
    {
        /** @var SettingDto[] $settings */
        $settings = Arr::sort(
            $this->settingRepository->find(),
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
                $this->event(new ProcessAlreadyActiveEvent($setting->id));

                continue;
            }

            $process = $this->processRepository->create(
                settingId: $setting->id,
                clearedCount: 0,
                clearedAt: null
            );

            $clearedCount = 0;

            while (true) {
                $traceIds = $this->traceRepository->findIds(
                    limit: $this->countInDeletionBatch,
                    loggedAtTo: $loggedAtTo,
                    type: $type,
                    excludedTypes: $excludedTypes
                );

                if (empty($traceIds)) {
                    break;
                }

                $this->traceRepository->delete(
                    traceIds: $traceIds
                );

                $this->traceTreeRepository->delete(
                    traceIds: $traceIds
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

    private function event(object $event): void
    {
        $this->app['events']->dispatch($event);
    }

    private function deleteOldProcesses(): void
    {
        $this->processRepository->delete(
            to: now()->subDays($this->daysLifetimeOfProcessData)
        );
    }
}
