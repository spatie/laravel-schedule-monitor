<?php

namespace Spatie\ScheduleMonitor\Models;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use OhDear\PhpSdk\Resources\CronCheck;
use Spatie\ScheduleMonitor\Jobs\PingOhDearJob;
use Spatie\ScheduleMonitor\Support\Concerns\UsesMonitoredScheduledTasks;
use Spatie\ScheduleMonitor\Support\Concerns\UsesScheduleMonitoringModels;
use Spatie\ScheduleMonitor\Support\ScheduledTasks\ScheduledTaskFactory;

class MonitoredScheduledTask extends Model
{
    use UsesScheduleMonitoringModels;
    use UsesMonitoredScheduledTasks;
    use HasFactory;

    public $guarded = [];

    protected $casts = [
        'registered_on_oh_dear_at' => 'datetime',
        'last_pinged_at' => 'datetime',
        'last_started_at' => 'datetime',
        'last_finished_at' => 'datetime',
        'last_skipped_at' => 'datetime',
        'last_failed_at' => 'datetime',
        'grace_time_in_minutes' => 'integer',
    ];

    public function logItems(): HasMany
    {
        return $this->hasMany($this->getMonitoredScheduleTaskLogItemModel(), 'monitored_scheduled_task_id')->orderByDesc('id');
    }

    public static function findByName(string $name): ?self
    {
        $monitoredScheduledTask = new static();

        return $monitoredScheduledTask
            ->getMonitoredScheduleTaskModel()
            ->where('name', $name)
            ->first();
    }

    public static function findForTask(Event $event): ?self
    {
        $task = ScheduledTaskFactory::createForEvent($event);
        $monitoredScheduledTask = new static();

        if (empty($task->name())) {
            return null;
        }

        return $monitoredScheduledTask
            ->getMonitoredScheduleTaskModel()
            ->findByName($task->name());
    }

    public static function findForCronCheck(CronCheck $cronCheck): ?self
    {
        $monitoredScheduledTask = new static();

        return $monitoredScheduledTask
            ->getMonitoredScheduleTaskModel()
            ->findByName($cronCheck->name);
    }

    public function markAsRegisteredOnOhDear(): self
    {
        if (is_null($this->registered_on_oh_dear_at)) {
            $this->update(['registered_on_oh_dear_at' => now()]);
        }

        return $this;
    }

    public function markAsStarting(ScheduledTaskStarting $event): self
    {
        $logItem = $this->createLogItem($this->getMonitoredScheduleTaskLogItemModel()::TYPE_STARTING);

        $logItem->updateMeta([
            'memory' => memory_get_usage(true),
        ]);

        $this->update([
            'last_started_at' => now(),
        ]);

        if (config('schedule-monitor.oh_dear.send_starting_ping') === true) {
            $this->pingOhDear($logItem);
        }

        return $this;
    }

    public function markAsFinished(ScheduledTaskFinished $event): self
    {
        if ($this->eventConcernsBackgroundTaskThatCompletedInForeground($event)) {
            return $this;
        }

        if ($event->task->exitCode !== 0 && ! is_null($event->task->exitCode)) {
            return $this->markAsFailed($event);
        }

        $logItem = $this->createLogItem($this->getMonitoredScheduleTaskLogItemModel()::TYPE_FINISHED);

        $logItem->updateMeta([
            'runtime' => $event->task->runInBackground ? 0 : $event->runtime,
            'exit_code' => $event->task->exitCode,
            'memory' => $event->task->runInBackground ? 0 : memory_get_usage(true),
            'output' => $this->getEventTaskOutput($event),
        ]);

        $this->update(['last_finished_at' => now()]);

        $this->pingOhDear($logItem);

        return $this;
    }

    public function eventConcernsBackgroundTaskThatCompletedInForeground(ScheduledTaskFinished $event): bool
    {
        if (! $event->task->runInBackground) {
            return false;
        }

        return $event->task->exitCode === null;
    }

    /**
     * @param ScheduledTaskFailed|ScheduledTaskFinished $event
     *
     * @return $this
     */
    public function markAsFailed($event): self
    {
        $logItem = $this->createLogItem($this->getMonitoredScheduleTaskLogItemModel()::TYPE_FAILED);

        if ($event instanceof ScheduledTaskFailed) {
            $logItem->updateMeta([
                'failure_message' => Str::limit(optional($event->exception)->getMessage(), 255),
            ]);
        }

        if ($event instanceof ScheduledTaskFinished) {
            $logItem->updateMeta([
                'runtime' => $event->runtime,
                'exit_code' => $event->task->exitCode,
                'memory' => memory_get_usage(true),
                'output' => $this->getEventTaskOutput($event),
            ]);
        }

        $this->update(['last_failed_at' => now()]);

        $this->pingOhDear($logItem);

        return $this;
    }

    public function markAsSkipped(ScheduledTaskSkipped $event): self
    {
        $this->createLogItem($this->getMonitoredScheduleTaskLogItemModel()::TYPE_SKIPPED);

        $this->update(['last_skipped_at' => now()]);

        return $this;
    }

    public function pingOhDear(MonitoredScheduledTaskLogItem $logItem): self
    {
        if (empty($this->ping_url)) {
            return $this;
        }

        if (! in_array($logItem->type, [
            $this->getMonitoredScheduleTaskLogItemModel()::TYPE_STARTING,
            $this->getMonitoredScheduleTaskLogItemModel()::TYPE_FAILED,
            $this->getMonitoredScheduleTaskLogItemModel()::TYPE_FINISHED,
        ], true)) {
            return $this;
        }

        dispatch(new PingOhDearJob($logItem));

        return $this;
    }

    public function createLogItem(string $type): MonitoredScheduledTaskLogItem
    {
        return $this->logItems()->create([
            'type' => $type,
        ]);
    }

    /**
     * @param ScheduledTaskFailed|ScheduledTaskFinished $event
     */
    public function getEventTaskOutput($event): ?string
    {
        if (! ($this->getMonitoredScheduledTasks()->getStoreOutputInDb($event->task) ?? false)) {
            return null;
        }

        if (is_null($event->task->output)) {
            return null;
        }

        if ($event->task->output === $event->task->getDefaultOutput()) {
            return null;
        }

        if (! is_file($event->task->output)) {
            return null;
        }

        $output = file_get_contents($event->task->output);

        return $output ?: null;
    }
}
