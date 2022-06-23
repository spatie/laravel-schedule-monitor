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
        'runtime' => 12.34,
        'exit_code' => 123,
        'memory' => 12345,
    ];

    $this->monitoredScheduledTaskLogItem = MonitoredScheduledTaskLogItem::factory()->create([
        'type' => MonitoredScheduledTaskLogItem::TYPE_FINISHED,
        'meta' => $this->meta,
    ]);
});

it('can ping oh dear when a scheduled task finishes', function () {
    dispatch(new PingOhDearJob($this->monitoredScheduledTaskLogItem));

    $this->assertEquals(
        $this->monitoredScheduledTaskLogItem->monitoredScheduledTask->refresh()->last_pinged_at->format('Y-m-d H:i:s'),
        now()->format('Y-m-d H:i:s'),
    );

    Http::assertSent(function (Request $request) {
        $this->assertEquals(
            $request->url(),
            $this->monitoredScheduledTaskLogItem->monitoredScheduledTask->ping_url . '/finished'
        );

        expect($request->data())->toEqual($this->meta);

        return true;
    });
});
