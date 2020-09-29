## Upgrading

### From v1 to v2

Add a column `timezone` (string, nullable) to the `monitored_scheduled_tasks` table. In existing rows you should fill to column with the timezone in your app.
