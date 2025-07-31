<?php

use Spatie\ScheduleMonitor\Commands\VerifyCommand;

it('can verify the connection to oh dear', function () {
    $this->artisan(VerifyCommand::class)->assertExitCode(0);
});

it('will throw an exception if the api token is not set', function () {
    config()->set('schedule-monitor.oh_dear.api_token', null);

    $this->expectException(Exception::class);

    $this->artisan(VerifyCommand::class)->assertExitCode(0);
});

it('will throw an exception if the site id is not set', function () {
    config()->set('schedule-monitor.oh_dear.monitor_id', null);

    $this->expectException(Exception::class);

    $this->artisan(VerifyCommand::class)->assertExitCode(0);
});
