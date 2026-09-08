<?php

declare(strict_types=1);

namespace App\Modules\Notification\Repositories;

use App\Models\Notifications\NotificationChannel;
use App\Modules\Notification\Repositories\Dto\ChannelDto;
use Illuminate\Database\Eloquent\Builder;

readonly class ChannelRepository
{
    /**
     * @return ChannelDto[]
     */
    public function find(?bool $enabled = null): array
    {
        return NotificationChannel::query()
            ->when(
                !is_null($enabled),
                fn(Builder $query) => $query->where('enabled', $enabled)
            )
            ->orderBy('id')
            ->get()
            ->map(fn(NotificationChannel $channel) => $this->makeDto($channel))
            ->all();
    }

    public function findById(int $id): ?ChannelDto
    {
        $channel = NotificationChannel::query()->find($id);

        return $channel instanceof NotificationChannel ? $this->makeDto($channel) : null;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function create(
        string $name,
        string $type,
        bool $enabled,
        bool $onOpened,
        bool $onEvent,
        bool $onClosed,
        array $settings
    ): ChannelDto {
        $channel = new NotificationChannel();

        $channel->name      = $name;
        $channel->type      = $type;
        $channel->enabled   = $enabled;
        $channel->on_opened = $onOpened;
        $channel->on_event  = $onEvent;
        $channel->on_closed = $onClosed;
        $channel->settings  = $settings;

        $channel->saveOrFail();

        return $this->makeDto($channel);
    }

    /**
     * Through the model rather than a query builder update: `settings` is an encrypted
     * cast, and a mass update writes the array past the cast as a raw value.
     *
     * @param array<string, mixed> $settings
     */
    public function update(
        int $id,
        string $name,
        bool $enabled,
        bool $onOpened,
        bool $onEvent,
        bool $onClosed,
        array $settings
    ): void {
        $channel = NotificationChannel::query()->find($id);

        if (!$channel instanceof NotificationChannel) {
            return;
        }

        $channel->name      = $name;
        $channel->enabled   = $enabled;
        $channel->on_opened = $onOpened;
        $channel->on_event  = $onEvent;
        $channel->on_closed = $onClosed;
        $channel->settings  = $settings;

        $channel->saveOrFail();
    }

    public function delete(int $id): void
    {
        NotificationChannel::query()->where('id', $id)->delete();
    }

    private function makeDto(NotificationChannel $channel): ChannelDto
    {
        return new ChannelDto(
            id: $channel->id,
            name: $channel->name,
            type: $channel->type,
            enabled: $channel->enabled,
            onOpened: $channel->on_opened,
            onEvent: $channel->on_event,
            onClosed: $channel->on_closed,
            settings: $channel->settings,
            createdAt: $channel->created_at,
            updatedAt: $channel->updated_at
        );
    }
}
