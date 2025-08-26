<?php

namespace Spatie\ScheduleMonitor\Tests;

use CreateScheduleMonitorTables;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\ScheduleMonitor\ScheduleMonitorServiceProvider;
use Spatie\ScheduleMonitor\Support\OhDear\OhDear;
use Spatie\ScheduleMonitor\Tests\TestClasses\FakeOhDear;
use Spatie\ScheduleMonitor\Tests\TestClasses\TestKernel;
use Symfony\Component\Console\Output\BufferedOutput;
use function Termwind\renderUsing;

class TestCase extends Orchestra
{
    protected FakeOhDear $ohDear;

    protected function setUp(): void
    {
        parent::setUp();



        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('schedule-monitor.oh_dear.api_token', 'oh-dear-test-token');
        config()->set('schedule-monitor.oh_dear.monitor_id', 1);

        TestKernel::clearScheduledCommands();

        $this->ohDear = new FakeOhDear();

        $this->app->bind(OhDear::class, fn () => $this->ohDear);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Spatie\\ScheduleMonitor\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        renderUsing(new BufferedOutput());
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
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('schedule-monitor.oh_dear.api_token', 'oh-dear-test-token');
        config()->set('schedule-monitor.oh_dear.monitor_id', 1);

        include_once __DIR__ . '/../database/migrations/create_schedule_monitor_tables.php.stub';
        (new CreateScheduleMonitorTables())->up();
    }
}
