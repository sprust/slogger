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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class WatcherTypeRouteGuardTest extends TestCase
{
    public function testEditingAWatcherOfAnotherTypeIsNotFound(): void
    {
        $updateAction = $this->createMock(UpdateWatcherAction::class);
        $updateAction->expects($this->never())->method('handle');

        $controller = new SlowTracesWatcherController(
            createWatcherAction: $this->createMock(CreateWatcherAction::class),
            updateWatcherAction: $updateAction,
            findWatcherAction: $this->findWatcherAction(WatcherTypeEnum::TracesSpike)
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->update(5, $this->request());
    }

    public function testEditingAWatcherOfTheRouteTypeGoesThrough(): void
    {
        $updateAction = $this->createMock(UpdateWatcherAction::class);
        $updateAction->expects($this->once())->method('handle');

        $controller = new SlowTracesWatcherController(
            createWatcherAction: $this->createMock(CreateWatcherAction::class),
            updateWatcherAction: $updateAction,
            findWatcherAction: $this->findWatcherAction(WatcherTypeEnum::SlowTraces)
        );

        $controller->update(5, $this->request());
    }

    public function testEditingAWatcherThatIsNotThereIsNotFound(): void
    {
        $controller = new SlowTracesWatcherController(
            createWatcherAction: $this->createMock(CreateWatcherAction::class),
            updateWatcherAction: $this->createMock(UpdateWatcherAction::class),
            findWatcherAction: $this->findWatcherAction(null)
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->update(5, $this->request());
    }

    private function request(): SlowTracesWatcherRequest
    {
        $request = SlowTracesWatcherRequest::create('/', 'PATCH', [
            'name'             => 'x',
            'enabled'          => true,
            'cooldown_seconds' => 300,
            'settings'         => ['duration' => 10, 'window_minutes' => 5],
        ]);

        $request->setContainer($this->app);
        $request->validateResolved();

        return $request;
    }

    private function findWatcherAction(?WatcherTypeEnum $storedType): FindWatcherAction
    {
        $action = $this->createMock(FindWatcherAction::class);

        $action->method('handle')->willReturn(
            is_null($storedType) ? null : $this->watcher($storedType)
        );

        return $action;
    }

    private function watcher(WatcherTypeEnum $type): WatcherObject
    {
        $now = Carbon::parse('2026-09-08 19:00:00');

        return new WatcherObject(
            id: 5,
            name: 'prod',
            type: $type,
            enabled: true,
            cooldownSeconds: 600,
            settings: new BufferOverflowSettingsObject(),
            match: null,
            collectSince: $now,
            lastCheckedAt: $now,
            lastTriggeredAt: $now,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
