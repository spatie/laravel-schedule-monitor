<?php

namespace Spatie\ScheduleMonitor\Commands;

use Exception;
use Illuminate\Console\Command;
use OhDear\PhpSdk\OhDear;

class VerifyCommand extends Command
{
    public $signature = 'schedule-monitor:verify';

    public $description = 'Verify that the Oh Dear connection is configured correctly';

    public function handle()
    {
        $ohDearConfig = config('schedule-monitor.oh_dear');

        $this->info('Verifying if Oh Dear is configured correctly...');
        $this->line('');

        $this
            ->verifySdkInstalled()
            ->verifyApiToken($ohDearConfig)
            ->verifySiteId($ohDearConfig)
            ->verifyConnection($ohDearConfig);

        $this->line('');
        $this->info('All ok!');
        $this->info('Run `php artisan schedule-monitor:sync` to sync your scheduled tasks with Oh Dear.');
    }

    public function verifySdkInstalled(): self
    {
        if (! class_exists(OhDear::class)) {
            throw new Exception("You must install the Oh Dear SDK in order to sync your schedule with Oh Dear. Run `composer require ohdearapp/ohdear-php-sdk`.");
        }

        $this->comment('The Oh Dear SDK is installed.');

        return $this;
    }

    protected function verifyApiToken(array $ohDearConfig): self
    {
        if (empty($ohDearConfig['api_token'])) {
            throw new Exception('No API token found. Make sure you added an API token to the `api_token` key of the `server-monitor` config file. You can generate a new token here: https://ohdear.app/user-settings/api');
        }

        $this->comment('Oh Dear API token found.');

        return $this;
    }

    protected function verifySiteId(array $ohDearConfig): self
    {
        if (empty($ohDearConfig['site_id'])) {
            throw new Exception('No site id found. Make sure you added an site id to the `site_id` key of the `server-monitor` config file. You can found your site id on the settings page of a site on Oh Dear.');
        }

        $this->comment('Oh Dear site id found.');

        return $this;
    }

    protected function verifyConnection(array $ohDearConfig)
    {
        $this->comment('Trying to reach Oh Dear...');

        $site = app(OhDear::class)->site($ohDearConfig['site_id']);

        $this->comment("Successfully connected to Oh Dear. The configured site URL is: {$site->sortUrl}");

        return $this;
    }
}
