# Monitor scheduled tasks in a Laravel app

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-schedule-monitor.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-schedule-monitor)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-schedule-monitor/run-tests?label=tests)](https://github.com/spatie/laravel-schedule-monitor/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-schedule-monitor.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-schedule-monitor)

This package will monitor your Laravel schedule. It will write an entry to a log table in the db each time a schedule tasks starts, end, fails or is skipped. Using the `list` command you can check when the schedule tasks have been executed.

![screenshot](https://github.com/spatie/laravel-schedule-monitor/blob/master/docs/list-with-failure.png)

Optionally, this package can sync your schedule with [Oh Dear](https://ohdear.app). Oh Dear will send you a notification whenever a scheduled task doesn't run on time or fails.

## Support us

Learn how to create a package like this one, by watching our premium video course:

[![Laravel Package training](https://spatie.be/github/package-training.jpg)](https://laravelpackage.training)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-schedule-monitor
```

You must publish and run migrations:

```bash
php artisan vendor:publish --provider="Spatie\ScheduleMonitor\ScheduleMonitorServiceProvider" --tag="migrations"
php artisan migrate
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Spatie\ScheduleMonitor\ScheduleMonitorServiceProvider" --tag="config"
```

This is the contents of the published config file:

```php
return [
    /*
     * Schedule monitor will log each start, finish and failure of all scheduled jobs.
     * After a while the `monitored_scheduled_task_log_items` might become big.
     * Here you can specify the amount of days log items should be kept.
     */
    'delete_log_items_older_than_days' => 30,

    /*
     * Oh Dear can notify you via Mail, Slack, SMS, web hooks, ... when a
     * scheduled task does not run on time.
     *
     */
    'oh_dear' => [
        /*
         * You can generate an API token at the Oh Dear user settings screen.
         */
        'api_token' => env('OH_DEAR_API_TOKEN', ''),

        /*
         *  The id of the site you want to sync the schedule with.
         *
         * You'll find this id on the settings page of a site at Oh Dear.
         */
        'site_id' => env('OH_DEAR_SITE_ID'),

        /*
         * To keep scheduled jobs as short as possible, Oh Dear will be pinged
         * via queued jobs. Here you can specify the name of the queue you wish to use.
         */
        'queue' => env('OH_DEAR_QUEUE'),
    ],
```

You must register these tasks in your console kernel:

- `schedule-monitor:sync`: this command is responsible for syncing your schedule with the database, and optionally Oh Dear. If you are using [Oh Dear](https://ohdear.app) for getting notifications, we recommend scheduling it at on odd time like '04:56', so the Oh Dear server doesn't get all the sync requests from all users at the same time.
- `schedule-monitor:clean`: this command will clean up old records from the schedule monitor log table.

```php
// app/Console/Kernel.php

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('schedule-monitor:sync')->dailyAt('04:56');
        $schedule->command('schedule-monitor:clean')->daily();
    }
}
```

Each time you change the schedule, we recommend manually running `schedule-monitor:sync` and `schedule:list`.

## Usage

To monitor your schedule you should first run `schedule-monitor:sync`. This command will take a look at your schedule and create an entry for each task in the `monitored_scheduled_tasks` table.

![screenshot](https://github.com/spatie/laravel-schedule-monitor/blob/master/docs/sync.png)


To view all monitored scheduled tasks, you can run `schedule-monitor:list`. This command will list all monitored scheduled tasks. It will show you when a scheduled task has last started, finished, or failed.

![screenshot](https://github.com/spatie/laravel-schedule-monitor/blob/master/docs/list.png)

The package will write an entry to the `monitored_scheduled_task_log_items` table in the db each time a schedule tasks starts, end, fails or is skipped. Take a look at the contest of that table if you want to know when and how scheduled tasks did execute. The log items also hold other interesting metrics like memory usage, execution time, and more.

### Naming tasks

Schedule monitor will try to automatically determine a name for a scheduled task. For commands this is the command name, for anonymous jobs the class name of the first argument will be used. For some tasks, like scheduled closures, a name cannot be determined automatically.

To manually set a name of the scheduled task,  you can tack on `monitorName()`.

Here's an example.

```php
// in app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
   $schedule->command('your-command')->daily()->monitorName('a-custom-name');
   $schedule->call(fn () => 1 + 1)->hourly()->monitorName('addition-closure');
}
```

When you change the name of task, the schedule monitor will remove all log items of the monitor with the old name, and create a new monitor using the new name of the task.

### Ignoring scheduled tasks

You can avoid a scheduled task being monitored by tacking on `doNotMonitor` when scheduling the task.

```php
// in app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
   $schedule->command('your-command')->daily()->doNotMonitor();
}
```

### Getting notified when a scheduled task doesn't finish in time

This package can sync your schedule with the [Oh Dear](https://ohdear.app) cron check. Oh Dear will send you a notification whenever a scheduled task does not finish on time.

This cron check at Oh Dear is currently in beta, and you'll have to [request](mailto:support@ohdear.app) the team at Oh Dear for early access to use this feature.

To get started you will first need to install the Oh Dear SDK.
 
```bash
composer require ohdearapp/ohdear-php-sdk
```
 
 Next you, need to make sure the `api_token` and `site_id` keys of the `schedule-monitor` are filled with an API token, and an Oh Dear site id. To verify that these values hold correct values you can run this command.

```bash
php artisan schedule-monitor:verify
```

![screenshot](https://github.com/spatie/laravel-schedule-monitor/blob/master/docs/verify.png)

To sync your schedule with Oh Dear run this command:

```bash
php artisan schedule-monitor:sync
```

![screenshot](https://github.com/spatie/laravel-schedule-monitor/blob/master/docs/sync-oh-dear.png)

After that, the `list` command should show that all the scheduled tasks in your app are registered on Oh Dear.

![screenshot](https://github.com/spatie/laravel-schedule-monitor/blob/master/docs/list-oh-dear.png)

To keep scheduled jobs as short as possible, Oh Dear will be pinged via queued jobs. To ensure speedy delivery to Oh Dear, and to avoid false positive notifications, we highly recommend creating a dedicated queue for these jobs. You can put the name of that queue in the `queue` key of the config file.

Oh Dear will wait for the completion of a schedule tasks for a given amount of minutes. This is called the grace time. By default, all scheduled tasks will have a grace time of 5 minutes. To customize this value, you can tack on `graceTimeInMinutes` to your scheduled tasks.

Here's an example where Oh Dear will send a notification if the task didn't finish by 00:10.

```php
// in app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
   $schedule->command('your-command')->daily()->graceTimeInMinutes(10);
}
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
