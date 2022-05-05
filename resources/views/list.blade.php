<div class="my-1 mx-2 space-y-1">
    <x-schedule-monitor::monitored-tasks
        :tasks="$monitoredTasks"
        :dateFormat="$dateFormat"
        :usingOhDear="$usingOhDear"
    />
    @if (! $readyForMonitoringTasks->isEmpty())
        <x-schedule-monitor::ready-for-monitoring-tasks
            :tasks="$readyForMonitoringTasks"
        />
    @endif
    @if (! $unnamedTasks->isEmpty())
        <x-schedule-monitor::unnamed-tasks
            :tasks="$unnamedTasks"
        />
    @endif
    @if (! $duplicateTasks->isEmpty())
        <x-schedule-monitor::duplicate-tasks
            :tasks="$duplicateTasks"
        />
    @endif
</div>
