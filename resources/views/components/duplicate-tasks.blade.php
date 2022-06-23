@props(['tasks'])
<div class="space-y-1">
    <x-schedule-monitor::title>Duplicate Tasks</x-schedule-monitor::title>

    <div>These tasks could not be monitored because they have a duplicate name.</div>

    <div>
        @foreach ($tasks as $task)
            <x-schedule-monitor::task :task="$task" />
        @endforeach
    </div>

    <div>
        To monitor these tasks you should add <span class="text-yellow font-bold">->monitorName()</span> in the schedule to manually specify a unique name.
    </div>
</div>
