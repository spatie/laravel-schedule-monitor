# Monitor scheduled tasks in a Laravel app

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-schedule-monitor.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-schedule-monitor)
![Tests](https://github.com/spatie/laravel-schedule-monitor/workflows/Tests/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-schedule-monitor.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-schedule-monitor)

This package will monitor your Laravel schedule. It will write an entry to a log table in the db each time a schedule tasks starts, end, fails or is skipped. Using the `list` command you can check when the scheduled tasks have been executed.

![screenshot](https://github.com/spatie/laravel-schedule-monitor/blob/master/docs/list-with-failure.png)

This package can also sync your schedule with [Oh Dear](https://ohdear.app). Oh Dear will send you a notification whenever a scheduled task doesn't run on time or fails.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-schedule-monitor.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-schedule-monitor)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-schedule-monitor
```

#### Preparing the database

You must run the command to create the migration and then run it:

```bash
php artisan schedule-monitor:create-migration
php artisan migrate
```

#### Publishing the config file

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Spatie\ScheduleMonitor\ScheduleMonitorServiceProvider" --tag="config"
```

This is the contents of the published config file:

```php
return [
    /*
     * The schedule monitor will log each start, finish and failure of all scheduled jobs.
     * After a while the `monitored_scheduled_task_log_items` might become big.
     * Here you can specify the amount of days log items should be kept.
     */
    'delete_log_items_older_than_days' => 30,

    /*
     * The date format used for all dates displayed on the output of commands
     * provided by this package.
     */
    'date_format' => 'Y-m-d H:i:s',

    /*
     * Oh Dear can notify you via Mail, Slack, SMS, web hooks, ... when a
     * scheduled task does not run on time.
     *
     * More info: https://ohdear.app/cron-checks
     */
    'oh_dear' => [
        /*
         * You can generate an API token at the Oh Dear user settings screen
         *
         * https://ohdear.app/user-settings/api
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
         * via a queued job. Here you can specify the name of the queue you wish to use.
         */
        'queue' => env('OH_DEAR_QUEUE'),
    ],
];
```

#### Cleaning the database

You must register the `schedule-monitor:clean` tasks in your console kernel. This command will clean up old records from the schedule monitor log table.

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

#### Syncing the schedule

Every time you deploy your application, you should execute the `schedule-monitor:sync` command

```bash
schedule-monitor:sync
```

This command is responsible for syncing your schedule with the database, and optionally Oh Dear. We highly recommend adding this command to the script that deploys your production environment.

In a non-production environment you should manually run `schedule-monitor:sync`. You can verify if everything synced correctly using `schedule-monitor:list`.

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

### Setting a grace time

When the package detects that the last run of a scheduled task did not run in time, the `schedule-monitor` list will display that task using a red background color. In this screenshot the task named `your-command` ran too late.

![screenshot](https://github.com/spatie/laravel-schedule-monitor/blob/master/docs/list-with-failure.png)

The package will determine that a task ran too late if it was not finished at the time it was supposed to run + the grace time. You can think of the grace time as the number of minutes that a task under normal circumstances needs to finish. By default, the package grants a grace time of 5 minutes to each task.

You can customize the grace time by using the `graceTimeInMinutes` method on a task. In this example a grace time of 10 minutes is used for the `your-command` task.

```php
// in app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
   $schedule->command('your-command')->daily()->graceTimeInMinutes(10);
}
```

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

## Unsupported methods

Currently, this package does not work for tasks that use these methods:

- `between`
- `unlessBetween`
- `when`
- `skip`

## Third party scheduled task monitors

We assume that, when your scheduled tasks do not run properly, a scheduled task that sends out notifications would probably not run either.  That's why this package doesn't send out notifications by itself.

These services can notify you when scheduled tasks do not run properly:

- [Oh Dear](https://ohdear.app)
- [thenping.me](https://thenping.me)
- [Cronbox](https://cronbox.app)
- [Healthchecks.io](https://healthchecks.io)
- [Cronitor](https://cronitor.io)
- [Cronhub](https://cronhub.io/)
- [DeadMansSnitch](https://deadmanssnitch.com/)
- [CronAlarm](https://www.cronalarm.com/)
- [PushMon](https://www.pushmon.com/)

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
