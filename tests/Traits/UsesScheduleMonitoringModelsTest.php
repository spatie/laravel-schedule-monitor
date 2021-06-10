<?php

namespace Spatie\ScheduleMonitor\Tests\Traits;

use Spatie\ScheduleMonitor\Exceptions\InvalidClassException;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\ScheduleMonitor\Traits\UsesScheduleMonitoringModels;

class UsesScheduleMonitoringModelsTest extends TestCase
{
    /** @test */
    public function it_can_resolve_schedule_monitoring_models()
    {
        $model = new class() {
            use UsesScheduleMonitoringModels;
        };

        $monitorScheduleTask = $model->getMonitoredScheduleTaskModel();
        $monitorScheduleTaskLogItem = $model->getMonitoredScheduleTaskLogItemModel();

        $this->assertInstanceOf(MonitoredScheduledTask::class, $monitorScheduleTask);
        $this->assertInstanceOf(MonitoredScheduledTaskLogItem::class, $monitorScheduleTaskLogItem);
    }
}
