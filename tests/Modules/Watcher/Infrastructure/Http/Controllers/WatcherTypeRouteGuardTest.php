<?php

namespace Tests\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Domain\Actions\Mutations\CreateWatcherAction;
use App\Modules\Watcher\Domain\Actions\Mutations\UpdateWatcherAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Controllers\SlowTracesWatcherController;
use App\Modules\Watcher\Infrastructure\Http\Requests\SlowTracesWatcherRequest;
use Illuminate\Support\Carbon;
use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use Tests\TestCase;

class WatcherTypeRouteGuardTest extends TestCase
{
    public function testEditingAWatcherOfAnotherTypeIsNotFound(): void
    {
        $updateAction = $this->createMock(UpdateWatcherAction::class);

        $updateAction->expects($this->never())->method('handle');

        $controller = new SlowTracesWatcherController(
            createWatcherByAdminUseCase: new \App\Modules\Watcher\Domain\UseCases\CreateWatcherByAdminUseCase($this->createMock(CreateWatcherAction::class)),
            updateWatcherByAdminUseCase: new \App\Modules\Watcher\Domain\UseCases\UpdateWatcherByAdminUseCase(
                findTypedWatcherAction: new \App\Modules\Watcher\Domain\Actions\Queries\FindTypedWatcherAction($this->findWatcherAction(WatcherTypeEnum::ManyTraces)),
                updateWatcherAction: $updateAction,
            ),
            findWatcherByAdminUseCase: new \App\Modules\Watcher\Domain\UseCases\FindWatcherByAdminUseCase(new \App\Modules\Watcher\Domain\Actions\Queries\FindTypedWatcherAction($this->findWatcherAction(WatcherTypeEnum::ManyTraces))),
        );

        $this->expectException(WatcherNotFoundException::class);

        $controller->update(
            id: 5,
            request: $this->request(),
        );
    }

    public function testEditingAWatcherOfTheRouteTypeGoesThrough(): void
    {
        $updateAction = $this->createMock(UpdateWatcherAction::class);

        $updateAction->expects($this->once())->method('handle');

        $controller = new SlowTracesWatcherController(
            createWatcherByAdminUseCase: new \App\Modules\Watcher\Domain\UseCases\CreateWatcherByAdminUseCase($this->createMock(CreateWatcherAction::class)),
            updateWatcherByAdminUseCase: new \App\Modules\Watcher\Domain\UseCases\UpdateWatcherByAdminUseCase(
                findTypedWatcherAction: new \App\Modules\Watcher\Domain\Actions\Queries\FindTypedWatcherAction($this->findWatcherAction(WatcherTypeEnum::SlowTraces)),
                updateWatcherAction: $updateAction,
            ),
            findWatcherByAdminUseCase: new \App\Modules\Watcher\Domain\UseCases\FindWatcherByAdminUseCase(new \App\Modules\Watcher\Domain\Actions\Queries\FindTypedWatcherAction($this->findWatcherAction(WatcherTypeEnum::SlowTraces))),
        );

        $controller->update(
            id: 5,
            request: $this->request(),
        );
    }

    public function testEditingAWatcherThatIsNotThereIsNotFound(): void
    {
        $controller = new SlowTracesWatcherController(
            createWatcherByAdminUseCase: new \App\Modules\Watcher\Domain\UseCases\CreateWatcherByAdminUseCase($this->createMock(CreateWatcherAction::class)),
            updateWatcherByAdminUseCase: new \App\Modules\Watcher\Domain\UseCases\UpdateWatcherByAdminUseCase(
                findTypedWatcherAction: new \App\Modules\Watcher\Domain\Actions\Queries\FindTypedWatcherAction($this->findWatcherAction(null)),
                updateWatcherAction: $this->createMock(UpdateWatcherAction::class),
            ),
            findWatcherByAdminUseCase: new \App\Modules\Watcher\Domain\UseCases\FindWatcherByAdminUseCase(new \App\Modules\Watcher\Domain\Actions\Queries\FindTypedWatcherAction($this->findWatcherAction(null))),
        );

        $this->expectException(WatcherNotFoundException::class);

        $controller->update(
            id: 5,
            request: $this->request(),
        );
    }

    private function request(): SlowTracesWatcherRequest
    {
        $request = SlowTracesWatcherRequest::create(
            uri: '/',
            method: 'PATCH',
            parameters: [
                'name'             => 'x',
                'enabled'          => true,
                'cooldown_seconds' => 300,
                // The rules ask for the key even when it is null.
                'notification_channel_id' => null,
                'notify_on_opened'        => true,
                'notify_on_event'         => false,
                'notify_on_closed'        => true,
                'settings'                => [
                    'duration'       => 10,
                    'window_minutes' => 5,
                ],
            ],
        );

        $request->setContainer($this->app);

        $request->validateResolved();

        return $request;
    }

    private function findWatcherAction(?WatcherTypeEnum $storedType): FindWatcherAction
    {
        $action = $this->createMock(FindWatcherAction::class);

        $action->method('handle')->willReturn(is_null($storedType) ? null : $this->watcher($storedType));

        return $action;
    }

    private function watcher(WatcherTypeEnum $type): WatcherObject
    {
        $now = Carbon::parse(
            time: '2026-09-08 19:00:00',
        );

        return new WatcherObject(
            id: 5,
            name: 'prod',
            type: $type,
            enabled: true,
            cooldownSeconds: 600,
            notificationChannelId: null,
            notifyOnOpened: true,
            notifyOnEvent: false,
            notifyOnClosed: true,
            settings: new BufferOverflowSettingsObject(),
            match: null,
            collectSince: $now,
            lastCheckedAt: $now,
            lastTriggeredAt: $now,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
