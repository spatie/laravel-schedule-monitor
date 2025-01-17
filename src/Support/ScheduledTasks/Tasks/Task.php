<?php

namespace Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks;

use Carbon\CarbonInterface;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Lorisleiva\CronTranslator\CronParsingException;
use Lorisleiva\CronTranslator\CronTranslator;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Support\Concerns\UsesMonitoredScheduledTasks;
use Spatie\ScheduleMonitor\Support\Concerns\UsesScheduleMonitoringModels;

abstract class Task
{
    use UsesScheduleMonitoringModels;
    use UsesMonitoredScheduledTasks;

    protected Event $event;

    protected string $uniqueId;

    protected ?MonitoredScheduledTask $monitoredScheduledTask = null;

    abstract public static function canHandleEvent(Event $event): bool;

    abstract public function defaultName(): ?string;

    abstract public function type(): string;

    public function __construct(Event $event)
    {
        $this->event = $event;

        $this->uniqueId = (string)Str::uuid();

        if (! empty($this->name())) {
            $this->monitoredScheduledTask = $this->getMonitoredScheduleTaskModel()->findByName($this->name());
        }
    }

    public function uniqueId(): string
    {
        return $this->uniqueId;
    }

    public function name(): ?string
    {
        return $this->getMonitoredScheduledTasks()->getMonitorName($this->event)
            ?? $this->defaultName();
    }

    public function shouldMonitor(): bool
    {
        $doNotMonitor = $this->getMonitoredScheduledTasks()
            ->getDoNotMonitor($this->event);
        if (! isset($doNotMonitor)) {
            return true;
        }

        return ! $doNotMonitor;
    }

    public function isBeingMonitored(): bool
    {
        return ! is_null($this->monitoredScheduledTask);
    }

    public function shouldMonitorAtOhDear(): bool
    {
        $doNotMonitorAtOhDear = $this->getMonitoredScheduledTasks()
            ->getDoNotMonitorAtOhDear($this->event);
        if (! isset($doNotMonitorAtOhDear)) {
            return true;
        }

        return ! $doNotMonitorAtOhDear;
    }

    public function isBeingMonitoredAtOhDear(): bool
    {
        if (! $this->isBeingMonitored()) {
            return false;
        }

        if (! $this->shouldMonitorAtOhDear()) {
            return false;
        }

        return ! empty($this->monitoredScheduledTask->ping_url);
    }

    public function previousRunAt(): CarbonInterface
    {
        $dateTime = (new CronExpression($this->cronExpression()))->getPreviousRunDate(now());

        return Date::instance($dateTime);
    }

    public function nextRunAt(?CarbonInterface $now = null): CarbonInterface
    {
        $dateTime = (new CronExpression($this->cronExpression()))->getNextRunDate(
            $now ?? now(),
            0,
            false,
            $this->timezone()
        );

        $date = Date::instance($dateTime);

        $date->setTimezone(config('app.timezone'));

        return $date;
    }

    public function lastRunStartedAt(): ?CarbonInterface
    {
        return optional($this->monitoredScheduledTask)->last_started_at;
    }

    public function lastRunFinishedAt(): ?CarbonInterface
    {
        return optional($this->monitoredScheduledTask)->last_finished_at;
    }

    public function lastRunFailedAt(): ?CarbonInterface
    {
        return optional($this->monitoredScheduledTask)->last_failed_at;
    }

    public function lastRunSkippedAt(): ?CarbonInterface
    {
        return optional($this->monitoredScheduledTask)->last_skipped_at;
    }

    public function lastRunFinishedTooLate(): bool
    {
        if (! $this->isBeingMonitored()) {
            return false;
        }

        $lastFinishedAt = $this->lastRunFinishedAt()
            ? $this->lastRunFinishedAt()
            : $this->monitoredScheduledTask->created_at->subSecond();

        $expectedNextRunStart = $this->nextRunAt($lastFinishedAt);
        $shouldHaveFinishedAt = $expectedNextRunStart->addMinutes($this->graceTimeInMinutes());

        return $shouldHaveFinishedAt->isPast();
    }

    public function lastRunFailed(): bool
    {
        if (! $this->isBeingMonitored()) {
            return false;
        }

        if (! $lastRunFailedAt = $this->lastRunFailedAt()) {
            return false;
        }

        if (! $lastRunStartedAt = $this->lastRunStartedAt()) {
            return true;
        }

        return $lastRunFailedAt->isAfter($lastRunStartedAt->subSecond());
    }

    public function graceTimeInMinutes()
    {
        return $this->getMonitoredScheduledTasks()->getGraceTimeInMinutes($this->event)
            ?? config('schedule-monitor.oh_dear.grace_time_in_minutes', 5);
    }

    public function cronExpression(): string
    {
        return $this->event->getExpression();
    }

    public function timezone(): string
    {
        return (string)$this->event->timezone;
    }

    public function humanReadableCron(): string
    {
        try {
            return CronTranslator::translate($this->cronExpression());
        } catch (CronParsingException $exception) {
            return $this->cronExpression();
        }
    }

    public function runsInBackground():bool
    {
        return $this->event->runInBackground;
    }
}
