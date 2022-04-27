<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\ScheduleMonitor\Jobs\PingOhDearJob;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Spatie\TestTime\TestTime;

beforeEach(function () {
    TestTime::freeze('Y-m-d H:i:s', '2020-01-01 00:00:00');

    Http::fake([
        'ping.ohdear.app/*' => Http::response('ok', 200),
    ]);

    $this->meta = [
        'failure_message' => 'failure',
    ];

    $this->monitoredScheduledTaskLogItem = MonitoredScheduledTaskLogItem::factory()->create([
        'type' => MonitoredScheduledTaskLogItem::TYPE_FAILED,
        'meta' => $this->meta,
    ]);
});

it('can ping oh dear when a scheduled task fails', function () {
    dispatch(new PingOhDearJob($this->monitoredScheduledTaskLogItem));

    $this->assertEquals(
        $this->monitoredScheduledTaskLogItem->monitoredScheduledTask->refresh()->last_pinged_at->format('Y-m-d H:i:s'),
        now()->format('Y-m-d H:i:s'),
    );

    Http::assertSent(function (Request $request) {
        $this->assertEquals(
            $request->url(),
            $this->monitoredScheduledTaskLogItem->monitoredScheduledTask->ping_url . '/failed'
        );

        expect($request->data())->toEqual($this->meta);

        return true;
    });
});
