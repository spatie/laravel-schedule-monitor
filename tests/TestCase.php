<?php

namespace Spatie\ScheduleMonitor\Tests;

use CreateScheduleMonitorTables;
use Illuminate\Contracts\Console\Kernel;
use OhDear\PhpSdk\OhDear;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\ScheduleMonitor\ScheduleMonitorServiceProvider;
use Spatie\ScheduleMonitor\Tests\TestClasses\FakeOhDear;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;

class TestCase extends Orchestra
{
    protected FakeOhDear $ohDear;

    public function setUp(): void
    {
        parent::setUp();

        TestKernel::clearScheduledCommands();

        $this->ohDear = new FakeOhDear();

        $this->app->bind(OhDear::class, fn () => $this->ohDear);

        $this->withFactories(__DIR__.'/database/factories');
    }

    protected function getPackageProviders($app)
    {
        return [
            ScheduleMonitorServiceProvider::class,
        ];
    }

    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton(Kernel::class, TestKernel::class);
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('schedule-monitor.oh_dear.api_token', 'oh-dear-test-token');
        config()->set('schedule-monitor.oh_dear.site_id', 1);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        include_once __DIR__ . '/../database/migrations/create_schedule_monitor_tables.php.stub';
        (new CreateScheduleMonitorTables())->up();
    }
}
