<?php

return [
    /*
     * The schedule monitor will log each start, finish and failure of all scheduled jobs.
     * After a while the `monitored_scheduled_task_log_items` might become big.
     * Here you can specify the amount of days log items should be kept.
     *
     * Use Laravel's pruning command to delete old `MonitoredScheduledTaskLogItem` models.
     * More info: https://laravel.com/docs/11.x/eloquent#pruning-models
     */
    'delete_log_items_older_than_days' => 30,

    /*
     * The date format used for all dates displayed on the output of commands
     * provided by this package.
     */
    'date_format' => 'Y-m-d H:i:s',

    'models' => [
        /*
         * The model you want to use as a MonitoredScheduledTask model needs to extend the
         * `Spatie\ScheduleMonitor\Models\MonitoredScheduledTask` Model.
         */
        'monitored_scheduled_task' => Spatie\ScheduleMonitor\Models\MonitoredScheduledTask::class,

        /*
         * The model you want to use as a MonitoredScheduledTaskLogItem model needs to extend the
         * `Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem` Model.
         */
        'monitored_scheduled_log_item' => Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem::class,
    ],

    /*
     * Oh Dear can notify you via Mail, Slack, SMS, web hooks, ... when a
     * scheduled task does not run on time.
     *
     * More info: https://ohdear.app/docs/features/cron-job-monitoring
     */
    'oh_dear' => [
        /*
         * You can generate an API token at the Oh Dear user settings screen
         *
         * https://ohdear.app/user/api-tokens
         */
        'api_token' => env('OH_DEAR_API_TOKEN', ''),

        /*
         *  The id of the monitor you want to sync the schedule with.
         *
         * You'll find this id on the settings page of a monitor at Oh Dear.
         */
        'monitor_id' => env('OH_DEAR_MONITOR_ID'),

        /*
         * To keep scheduled jobs as short as possible, Oh Dear will be pinged
         * via a queued job. Here you can specify the name of the queue you wish to use.
         */
        'queue' => env('OH_DEAR_QUEUE'),

        /*
         * The job class that will be dispatched to ping Oh Dear.
         */
        'ping_oh_dear_job' => Spatie\ScheduleMonitor\Jobs\PingOhDearJob::class,

        /*
         * `PingOhDearJob`s will automatically be skipped if they've been queued for
         * longer than the time configured here.
         */
        'retry_job_for_minutes' => 10,

        /*
         * When set to true, we will automatically add the `PingOhDearJob` to Horizon's
         * silenced jobs.
         */
        'silence_ping_oh_dear_job_in_horizon' => true,

        /*
         * Send the start of a scheduled job to Oh Dear. This is not needed
         * for notifications to work correctly.
         */
        'send_starting_ping' => env('OH_DEAR_SEND_STARTING_PING', false),

        /**
         * The amount of minutes a scheduled task is allowed to run before it is
         * considered late.
         */
        'grace_time_in_minutes' => 5,

        /**
         * Which endpoint to ping on Oh Dear.
         */
        'endpoint_url' => env('OH_DEAR_PING_ENDPOINT_URL'),

        /**
         * The URL of the Oh Dear API.
         */
        'api_url' => env('OH_DEAR_API_URL', 'https://ohdear.app/api/'),
    ],
];
