<?php

namespace Spatie\ScheduleMonitor\Commands;

use Exception;
use Illuminate\Console\Command;
use Spatie\ScheduleMonitor\Support\OhDear\OhDear;
use function Termwind\render;

class VerifyCommand extends Command
{
    public $signature = 'schedule-monitor:verify';

    public $description = 'Verify that the Oh Dear connection is configured correctly';

    public function handle()
    {
        $ohDearConfig = config('schedule-monitor.oh_dear');

        render(view('schedule-monitor::alert', [
            'message' => 'Verifying if Oh Dear is configured correctly...',
        ]));

        $this
            ->verifySdkInstalled()
            ->verifyApiToken($ohDearConfig)
            ->verifyMonitorId($ohDearConfig)
            ->verifyConnection($ohDearConfig);

        render(view('schedule-monitor::alert', [
            'message' => <<<HTML
                <b class="bg-green text-white px-1">All ok!</b> Run <b class="text-yellow">php artisan schedule-monitor:sync</b>
                to sync your scheduled tasks with <b class="bg-red-700 text-white px-1">oh dear</b>.
            HTML,
        ]));
    }

    public function verifySdkInstalled(): self
    {
        if (! class_exists(OhDear::class)) {
            throw new Exception("You must install the Oh Dear SDK in order to sync your schedule with Oh Dear. Run `composer require ohdearapp/ohdear-php-sdk`.");
        }

        render(view('schedule-monitor::alert', [
            'message' => 'The Oh Dear SDK is installed.',
        ]));

        return $this;
    }

    protected function verifyApiToken(array $ohDearConfig): self
    {
        if (empty($ohDearConfig['api_token'])) {
            throw new Exception('No API token found. Make sure you added an API token to the `api_token` key of the `schedule-monitor` config file. You can generate a new token here: https://ohdear.app/user/api-tokens');
        }

        render(view('schedule-monitor::alert', [
            'message' => 'Oh Dear API token found.',
        ]));

        return $this;
    }

    protected function verifyMonitorId(array $ohDearConfig): self
    {
        if (empty($ohDearConfig['monitor_id'])) {
            throw new Exception('No monitor id found. Make sure you added an monitor id to the `monitor_id` key of the `schedule-monitor` config file. You can found your monitor id on the settings page of a monitor on Oh Dear.');
        }

        render(view('schedule-monitor::alert', [
            'message' => 'Oh Dear monitor id found.',
        ]));

        return $this;
    }

    protected function verifyConnection(array $ohDearConfig)
    {
        $this->comment('Trying to reach Oh Dear...');

        $monitor = app(OhDear::class)->monitor($ohDearConfig['monitor_id']);

        render(view('schedule-monitor::alert', [
            'message' => "Successfully connected to Oh Dear. The configured monitor URL is: {$monitor['sort_url']}",
        ]));

        return $this;
    }
}
