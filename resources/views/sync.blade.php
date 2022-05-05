<div class="mx-2 my-1 space-y-1">
    <div>All done! Now monitoring {{ $monitoredScheduledTasksCount }} {{ str()->plural('scheduled task', $monitoredScheduledTasksCount) }}.</div>
    <div>Run <span class="text-yellow font-bold">php artisan schedule-monitor:list</span> to see which jobs are now monitored.</div>
</div>
