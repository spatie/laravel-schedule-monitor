## Upgrading

### From v3 to v4

The Oh Dear integration has been simplified. If you want to sync with Oh Dear, you don't need to install the separate Oh Dear SDK package anymore.

Also, in Oh Dear, "sites" have been renamed "monitors".  In your `config/schedule-monitor.php` you should replace the old `ohdear.site_id` section with this piece from the new config file.

```php
        /*
         *  The id of the monitor you want to sync the schedule with.
         *
         * You'll find this id on the settings page of a monitor at Oh Dear.
         */
        'monitor_id' => env('OH_DEAR_MONITOR_ID'),
```

You should also rename `OH_DEAR_SITE_ID` to `OH_DEAR_MONITOR_ID` in your `.env` file.

### From v2 to v3

The `CleanCommand` has been removed. You can now use Laravel's pruning feature to [delete old log items](https://github.com/spatie/laravel-schedule-monitor#cleaning-the-database).

### From v1 to v2

Add a column `timezone` (string, nullable) to the `monitored_scheduled_tasks` table. In existing rows you should fill to column with the timezone in your app.
