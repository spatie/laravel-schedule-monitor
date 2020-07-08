<?php

namespace Spatie\ScheduleMonitor\Tests\Commands;

use Exception;
use Spatie\ScheduleMonitor\Commands\VerifyCommand;
use Spatie\ScheduleMonitor\Tests\TestCase;

class VerifyCommandTest extends TestCase
{
    /** @test */
    public function it_can_verify_the_connection_to_oh_dear()
    {
        $this->artisan(VerifyCommand::class)->assertExitCode(0);
    }

    /** @test */
    public function it_will_throw_an_exception_if_the_api_token_is_not_set()
    {
        config()->set('schedule-monitor.oh_dear.api_token', null);

        $this->expectException(Exception::class);

        $this->artisan(VerifyCommand::class)->assertExitCode(0);
    }

    /** @test */
    public function it_will_throw_an_exception_if_the_site_id_is_not_set()
    {
        config()->set('schedule-monitor.oh_dear.site_id', null);

        $this->expectException(Exception::class);

        $this->artisan(VerifyCommand::class)->assertExitCode(0);
    }
}
