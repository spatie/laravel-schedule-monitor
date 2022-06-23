@props(['tasks'])
<div class="space-y-1">
    <x-schedule-monitor::title>Run sync to start monitoring</x-schedule-monitor::title>

    <div>These tasks will be monitored after running: <span class="text-yellow font-bold">php artisan schedule-monitor:sync</span></div>

    <div>
        @foreach ($tasks as $task)
            <x-schedule-monitor::task :task="$task" />
        @endforeach
    </div>
</div>
