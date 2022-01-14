## Upgrading

### From v2 to v3

The `CleanCommand` has been removed. You can now use Laravel's pruning feature to [delete old log items](https://github.com/spatie/laravel-schedule-monitor#cleaning-the-database).

### From v1 to v2

Add a column `timezone` (string, nullable) to the `monitored_scheduled_tasks` table. In existing rows you should fill to column with the timezone in your app.
