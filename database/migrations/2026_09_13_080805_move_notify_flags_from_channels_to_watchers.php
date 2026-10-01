<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('watchers', function (Blueprint $table) {
            $table->boolean('notify_on_opened')->default(true)->after('notification_channel_id');
            $table->boolean('notify_on_event')->default(false)->after('notify_on_opened');
            $table->boolean('notify_on_closed')->default(false)->after('notify_on_event');
        });

        DB::table('watchers')
            ->join('notification_channels', 'notification_channels.id', '=', 'watchers.notification_channel_id')
            ->update([
                'watchers.notify_on_opened' => DB::raw('notification_channels.on_opened'),
                'watchers.notify_on_event'  => DB::raw('notification_channels.on_event'),
                'watchers.notify_on_closed' => DB::raw('notification_channels.on_closed'),
            ]);

        Schema::table('notification_channels', function (Blueprint $table) {
            $table->dropColumn(['on_opened', 'on_event', 'on_closed']);
        });
    }

    public function down(): void
    {
        Schema::table('notification_channels', function (Blueprint $table) {
            $table->boolean('on_opened')->default(true)->after('settings');
            $table->boolean('on_event')->default(false)->after('on_opened');
            $table->boolean('on_closed')->default(true)->after('on_event');
        });

        Schema::table('watchers', function (Blueprint $table) {
            $table->dropColumn(['notify_on_opened', 'notify_on_event', 'notify_on_closed']);
        });
    }
};
