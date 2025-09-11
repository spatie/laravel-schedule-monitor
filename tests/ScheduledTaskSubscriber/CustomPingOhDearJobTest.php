<?php

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\ScheduleMonitor\Jobs\PingOhDearJob;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Spatie\ScheduleMonitor\Tests\TestClasses\CustomPingOhDearJob;
use Spatie\TestTime\TestTime;



beforeEach(function () {
    TestTime::freeze('Y-m-d H:i:s', '2020-01-01 00:00:00');

    Http::fake([
        'ping.ohdear.app/*' => Http::response('ok', 200),
    ]);

    Queue::fake();
});

it('can use a custom ping oh dear job class from config', function () {
    Config::set('schedule-monitor.oh_dear.ping_oh_dear_job', CustomPingOhDearJob::class);

    $monitoredTask = MonitoredScheduledTask::factory()->create([
        'ping_url' => 'https://ping.ohdear.app/test-uuid',
    ]);

    $logItem = MonitoredScheduledTaskLogItem::factory()->create([
        'monitored_scheduled_task_id' => $monitoredTask->id,
        'type' => MonitoredScheduledTaskLogItem::TYPE_FINISHED,
    ]);

    // Trigger the ping (this is normally done via events)
    $monitoredTask->pingOhDear($logItem);

    Queue::assertPushed(CustomPingOhDearJob::class);
});
