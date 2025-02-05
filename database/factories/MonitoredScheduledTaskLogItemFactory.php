<?php

namespace Spatie\ScheduleMonitor\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

class MonitoredScheduledTaskLogItemFactory extends Factory
{
    protected $model = MonitoredScheduledTaskLogItem::class;

    public function definition(): array
    {
        return [
            'monitored_scheduled_task_id' => MonitoredScheduledTask::factory(),
            'type' => $this->faker->randomElement([
                MonitoredScheduledTaskLogItem::TYPE_STARTING,
                MonitoredScheduledTaskLogItem::TYPE_FINISHED,
                MonitoredScheduledTaskLogItem::TYPE_SKIPPED,
            ]),
            'meta' => [],
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function(MonitoredScheduledTaskLogItem $logItem) {
            $scheduledTask = $logItem->monitoredScheduledTask;

            $scheduledTask->ping_url = config('schedule-monitor.oh_dear.endpoint_url', 'https://ping.ohdear.app');
            $scheduledTask->save();

            return $logItem;
        });
    }
}
