<?php

namespace Spatie\ScheduleMonitor\Contracts;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OhDear\PhpSdk\Resources\CronCheck;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

interface MonitoredScheduledTask
{
    public function logItems(): HasMany;
    public static function findByName(string $name): ?self;
    public static function findForTask(Event $event): ?self;
    public static function findForCronCheck(CronCheck $cronCheck): ?self;
    public function markAsRegisteredOnOhDear(): self;
    public function markAsStarting(ScheduledTaskStarting $event): self;
    public function markAsFinished(ScheduledTaskFinished $event): self;
    public function eventConcernsBackgroundTaskThatCompletedInForeground(ScheduledTaskFinished $event): bool;
    public function markAsFailed($event): self;
    public function markAsSkipped(ScheduledTaskSkipped $event): self;
    public function pingOhDear(MonitoredScheduledTaskLogItem $logItem): self;
    public function createLogItem(string $type): MonitoredScheduledTaskLogItem;
    public function getEventTaskOutput($event): ?string;
}
