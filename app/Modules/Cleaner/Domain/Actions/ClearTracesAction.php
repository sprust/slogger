<?php

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Contracts\Actions\ClearTracesActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\ProcessRepositoryInterface;
use App\Modules\Cleaner\Contracts\Repositories\SettingRepositoryInterface;
use App\Modules\Cleaner\Domain\Events\ProcessAlreadyActiveEvent;
use App\Modules\Cleaner\Entities\SettingObject;
use App\Modules\Common\Domain\Service\EventsDispatcher;
use App\Modules\Trace\Contracts\Actions\Mutations\ClearTracesActionInterface as TraceClearTracesActionInterface;
use App\Modules\Trace\Contracts\Actions\Mutations\DeleteTracesActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindMinLoggedAtTracesActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceIdsActionInterface;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Parameters\ClearTracesParameters;
use App\Modules\Trace\Parameters\DeleteTracesParameters;
use Illuminate\Support\Arr;
use Throwable;

readonly class ClearTracesAction implements ClearTracesActionInterface
{
    public function __construct(
        private EventsDispatcher $eventsDispatcher,
        private SettingRepositoryInterface $settingRepository,
        private ProcessRepositoryInterface $processRepository,
        private FindMinLoggedAtTracesActionInterface $findMinLoggedAtTracesAction,
        private TraceClearTracesActionInterface $clearTracesAction,
        private FindTraceIdsActionInterface $findTraceIdsAction,
        private DeleteTracesActionInterface $deleteTracesAction,
    ) {
    }

    /**
     * @throws TraceDynamicIndexErrorException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     */
    public function handle(): void
    {
        /** @var SettingObject[] $settings */
        $settings = Arr::sort(
            $this->settingRepository->find(deleted: false),
            fn(SettingObject $setting) => is_null($setting->type) ? 1 : 0
        );

        if (empty($settings)) {
            return;
        }

        $loggedAtFrom = $this->findMinLoggedAtTracesAction->handle();

        if (!$loggedAtFrom) {
            return;
        }

        $customizedTypes = array_unique(
            array_filter(
                array_map(
                    fn(SettingObject $setting) => $setting->type,
                    $settings
                )
            )
        ) ?: null;

        $now = now('UTC');

        foreach ($settings as $setting) {
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

            $loggedAtTo = $now->clone()->subDays($setting->daysLifetime);

            $type = $setting->type;
            $excludedTypes = is_null($type) ? $customizedTypes : null;

            $process = $this->processRepository->create(
                settingId: $setting->id,
                clearedCount: 0,
                clearedAt: null
            );

            $clearedCount = 0;

            $exception = null;

            while (true) {
                $traceIds = $this->findTraceIdsAction->handle(
                    limit: 1000,
                    loggedAtTo: $loggedAtTo,
                    type: $type,
                    excludedTypes: $excludedTypes
                );

                if (count($traceIds) === 0) {
                    break;
                }

                try {
                    if ($setting->onlyData) {
                        $clearedCount += $this->clearTracesAction->handle(
                            new ClearTracesParameters(
                                traceIds: $traceIds
                            )
                        );
                    } else {
                        $clearedCount += $this->deleteTracesAction->handle(
                            new DeleteTracesParameters(
                                traceIds: $traceIds
                            )
                        );
                    }
                } catch (Throwable $exception) {
                    $this->processRepository->update(
                        processId: $process->id,
                        clearedCount: $clearedCount,
                        clearedAt: now(),
                        exception: $exception
                    );

                    break;
                }

                $this->processRepository->update(
                    processId: $process->id,
                    clearedCount: $clearedCount,
                    clearedAt: null
                );
            }

            if (!$exception && $clearedCount === 0) {
                $this->processRepository->deleteByProcessId(
                    processId: $process->id
                );
            } else {
                $this->processRepository->update(
                    processId: $process->id,
                    clearedCount: $clearedCount,
                    clearedAt: now(),
                    exception: $exception
                );
            }
        }
    }
}
