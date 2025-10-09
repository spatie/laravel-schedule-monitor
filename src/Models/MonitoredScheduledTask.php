<?php

namespace Spatie\ScheduleMonitor\Models;

use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\ScheduleMonitor\Jobs\PingOhDearJob;
use Spatie\ScheduleMonitor\Support\Concerns\UsesMonitoredScheduledTasks;
use Spatie\ScheduleMonitor\Support\Concerns\UsesScheduleMonitoringModels;
use Spatie\ScheduleMonitor\Support\OhDear\CronCheck;
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
        $logItem = $this->createLogItem(
            $this->getMonitoredScheduleTaskLogItemModel()::TYPE_STARTING,
            ['memory' => memory_get_usage(true)]
        );

        $this->update(['last_started_at' => now()]);

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

        $logItem = $this->createLogItem(
            $this->getMonitoredScheduleTaskLogItemModel()::TYPE_FINISHED,
            [
                'runtime' => $event->task->runInBackground ? 0 : $event->runtime,
                'exit_code' => $event->task->exitCode,
                'memory' => $event->task->runInBackground ? 0 : memory_get_usage(true),
                'output' => $this->getEventTaskOutput($event),
            ]
        );

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
     * Mark a task as failed and create/update log item.
     *
     * Handles dual-event scenario in Laravel 12 where both ScheduledTaskFinished
     * and ScheduledTaskFailed fire for the same failure.
     *
     * @param ScheduledTaskFailed|ScheduledTaskFinished $event
     * @return $this
     */
    public function markAsFailed($event): self
    {
        // Check if we already have a log from a previous event
        $logItem = $this->findExistingFailedLog($event->task);

        if ($logItem) {
            // Second event: merge additional metadata
            $meta = $this->buildFailureMetadata($event, $logItem->meta);
            $logItem->updateMeta($meta);
        } else {
            // First event: create log with metadata and update timestamp
            $meta = $this->buildFailureMetadata($event);
            $logItem = $this->createFailedLogItem($event, $meta);
            $this->update(['last_failed_at' => now()]);
        }

        // Ping OhDear if this is the final event
        if ($this->shouldPing($event)) {
            $this->pingOhDear($logItem);
        }

        return $this;
    }

    /**
     * Determine if we should ping OhDear for a failed task event.
     *
     * Ping strategy to avoid duplicate pings:
     *
     * Event firing order in Laravel 12+ for NON-BACKGROUND failed tasks:
     *   1. ScheduledTaskFinished fires FIRST (exitCode != 0)
     *   2. ScheduledTaskFailed fires SECOND (in catch block with exception)
     *
     * Background tasks NEVER fire ScheduledTaskFailed (use ScheduledBackgroundTaskFinished instead)
     * Laravel 9-11: ScheduledTaskFailed doesn't fire for exit code failures, only ScheduledTaskFinished
     *
     * Therefore:
     * - ScheduledTaskFailed: Always ping (final event with complete metadata)
     * - ScheduledTaskFinished:
     *   - Background tasks: ping (ScheduledTaskFailed never fires for background tasks)
     *   - Non-background in Laravel 12+: skip ping (ScheduledTaskFailed will follow)
     *   - Non-background in Laravel 9-11: ping (ScheduledTaskFailed doesn't fire)
     *
     * @param ScheduledTaskFailed|ScheduledTaskFinished $event
     * @return bool
     */
    private function shouldPing($event): bool
    {
        if ($event instanceof ScheduledTaskFailed) {
            // ScheduledTaskFailed is the final event, always ping
            return true;
        }

        if ($event instanceof ScheduledTaskFinished) {
            if ($event->task->runInBackground) {
                // Background tasks: ScheduledTaskFailed won't fire even in Laravel 12
                return true;
            }

            // Non-background tasks: check Laravel version
            // In Laravel 12+, ScheduledTaskFailed will fire after this
            // In Laravel 9-11, ScheduledTaskFailed won't fire
            $laravelVersion = app()->version();

            return version_compare($laravelVersion, '12.0', '<');
        }

        return false;
    }

    /**
     * Build failure metadata for the given event.
     *
     * @param ScheduledTaskFailed|ScheduledTaskFinished $event
     * @param array|null $existingMeta Existing metadata to check for failure_message
     * @return array
     */
    private function buildFailureMetadata($event, ?array $existingMeta = null): array
    {
        if ($event instanceof ScheduledTaskFailed) {
            $meta = ['exit_code' => $event->task->exitCode];

            // Only set exception details if exception exists
            if ($event->exception) {
                $meta['failure_message'] = Str::limit($event->exception->getMessage(), 252);
                $meta['exception_class'] = get_class($event->exception);
            }

            return $meta;
        }

        if ($event instanceof ScheduledTaskFinished) {
            $meta = [
                'runtime' => $event->runtime,
                'exit_code' => $event->task->exitCode,
                'memory' => memory_get_usage(true),
                'output' => $this->getEventTaskOutput($event),
            ];

            // Extract failure message if not already set
            if (! isset($existingMeta['failure_message'])) {
                $meta['failure_message'] = $this->extractFailureMessageFromTask($event->task);
            }

            return $meta;
        }

        return [];
    }

    /**
     * Find existing failed log item for this task execution.
     *
     * @param mixed $task
     * @return MonitoredScheduledTaskLogItem|null
     */
    private function findExistingFailedLog($task): ?MonitoredScheduledTaskLogItem
    {
        if (! property_exists($task, '_scheduleMonitorFailedLogId')) {
            return null;
        }

        return $this->logItems()->find($task->_scheduleMonitorFailedLogId);
    }

    /**
     * Create a new failed log item and mark the task object to prevent duplicates.
     *
     * @param ScheduledTaskFailed|ScheduledTaskFinished $event
     * @param array $meta
     * @return MonitoredScheduledTaskLogItem
     */
    private function createFailedLogItem($event, array $meta): MonitoredScheduledTaskLogItem
    {
        $logItem = $this->createLogItem(
            $this->getMonitoredScheduleTaskLogItemModel()::TYPE_FAILED,
            $meta
        );

        // Store the log ID on the task object to prevent duplicate logs
        $event->task->_scheduleMonitorFailedLogId = $logItem->id;

        return $logItem;
    }

    /**
     * Extract failure message from task (reads output and parses).
     * Used for Laravel 9/10/11 compatibility where ScheduledTaskFailed doesn't fire.
     */
    protected function extractFailureMessageFromTask($task): string
    {
        $output = $this->readTaskOutputFile($task);

        return $this->extractFailureMessageFromOutput($output, $task->exitCode);
    }

    /**
     * Extract a human-readable failure message from task output.
     * Tries multiple patterns and falls back to generic message.
     */
    protected function extractFailureMessageFromOutput(?string $output, int $exitCode): string
    {
        if ($output) {
            // Try to find exception message
            if (preg_match('/Exception: (.+?)(?:\n|$)/i', $output, $matches)) {
                return Str::limit($matches[1], 252);
            }

            // Try to find error message
            if (preg_match('/Error: (.+?)(?:\n|$)/i', $output, $matches)) {
                return Str::limit($matches[1], 252);
            }

            // Use last non-empty line
            $lines = array_filter(explode("\n", trim($output)));
            if (! empty($lines)) {
                return Str::limit(end($lines), 252);
            }
        }

        // Fallback: generic message with exit code
        return "Command failed with exit code {$exitCode}";
    }

    /**
     * Read task output file if it exists and is not the default output.
     * Returns null if no output available.
     */
    private function readTaskOutputFile($task): ?string
    {
        if (is_null($task->output)) {
            return null;
        }

        if ($task->output === $task->getDefaultOutput()) {
            return null;
        }

        if (! is_file($task->output)) {
            return null;
        }

        return file_get_contents($task->output) ?: null;
    }

    public function markAsSkipped(ScheduledTaskSkipped $event): self
    {
        $this->createLogItem($this->getMonitoredScheduleTaskLogItemModel()::TYPE_SKIPPED);

        $this->update(['last_skipped_at' => now()]);

        return $this;
    }

    public function markAsBackgroundTaskFinished($event): self
    {
        // For background tasks, exitCode is available in ScheduledBackgroundTaskFinished event
        if ($event->task->exitCode === 0) {
            return $this->markBackgroundTaskAsFinished($event);
        }

        return $this->markBackgroundTaskAsFailed($event);
    }

    protected function markBackgroundTaskAsFinished($event): self
    {
        $logItem = $this->createLogItem(
            $this->getMonitoredScheduleTaskLogItemModel()::TYPE_FINISHED,
            [
                'exit_code' => $event->task->exitCode,
                'output' => $this->getBackgroundTaskOutput($event->task),
            ]
        );

        $this->update(['last_finished_at' => now()]);

        $this->pingOhDear($logItem);

        return $this;
    }

    protected function markBackgroundTaskAsFailed($event): self
    {
        $output = $this->getBackgroundTaskOutput($event->task);

        $logItem = $this->createLogItem(
            $this->getMonitoredScheduleTaskLogItemModel()::TYPE_FAILED,
            [
                'exit_code' => $event->task->exitCode,
                'output' => $output,
                'failure_message' => $this->extractFailureMessageFromOutput($output, $event->task->exitCode),
            ]
        );

        $this->update(['last_failed_at' => now()]);

        $this->pingOhDear($logItem);

        return $this;
    }

    /**
     * Get background task output - always reads if available.
     * Background tasks bypass the storeOutputInDb config.
     */
    protected function getBackgroundTaskOutput($task): ?string
    {
        return $this->readTaskOutputFile($task);
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

        $jobClass = config('schedule-monitor.oh_dear.ping_oh_dear_job') ?: PingOhDearJob::class;

        dispatch(new $jobClass($logItem));

        return $this;
    }

    public function createLogItem(string $type, array $meta = []): MonitoredScheduledTaskLogItem
    {
        return $this->logItems()->create([
            'type' => $type,
            'meta' => $meta,
        ]);
    }

    /**
     * Get event task output - respects storeOutputInDb config.
     * Only reads if explicitly configured to store output in database.
     *
     * @param ScheduledTaskFailed|ScheduledTaskFinished $event
     */
    public function getEventTaskOutput($event): ?string
    {
        if (! ($this->getMonitoredScheduledTasks()->getStoreOutputInDb($event->task) ?? false)) {
            return null;
        }

        return $this->readTaskOutputFile($event->task);
    }
}
