<?php

use Spatie\ScheduleMonitor\Jobs\PingOhDearJob;

it('will silence the PingOhDearJob by default', function() {
    config()->set('horizon.silenced', [PingOhDearJob::class]);

    expect(config('horizon.silenced'))->toContain(PingOhDearJob::class);
});
