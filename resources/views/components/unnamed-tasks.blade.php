@props(['tasks'])
<div class="space-y-1">
    <x-schedule-monitor::title>Unnamed Tasks</x-schedule-monitor::title>

    <div>These tasks cannot be monitored because no name could be determined for them.</div>

    <div>
        @foreach ($tasks as $task)
            <x-schedule-monitor::task :task="$task" />
        @endforeach
    </div>

    <div>To monitor these tasks you should add <span class="text-yellow font-bold">->monitorName()</span> in the schedule to manually specify a name.</div>
</div>
