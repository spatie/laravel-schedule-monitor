@props(['tasks', 'dateFormat', 'usingOhDear'])
<div class="space-y-1">
    <x-schedule-monitor::title>Monitored Tasks</x-schedule-monitor::title>

    <div class="space-y-1">
        @forelse ($tasks as $task)
            <div>
                <x-schedule-monitor::task :task="$task" />
                <div class="ml-2">
                    <div>
                        <span>
                            <span class="w-14 text-gray-500">⇁ Started at:</span>
                            <span class="date-width">
                                {{ optional($task->lastRunStartedAt())->format($dateFormat) ?? '--' }}
                            </span>
                        </span>
                        <span class="ml-3">
                            <span class="w-15 text-gray-500">⇁ Finished at:</span>
                            <span class="date-width {{ $task->lastRunFinishedTooLate() && $task->lastRunFinishedAt() ? 'text-red' : '' }}">
                                {{ optional($task->lastRunFinishedAt())->format($dateFormat) ?? '--' }}
                            </span>
                        </span>
                        <br class="xl:hidden">
                        <span class="xl:ml-3">
                            <span class="w-14 text-gray-500">⇁ Failed at:</span>
                            <span class="date-width {{ $task->lastRunFailed() ? 'text-red' : '' }}">
                                {{ optional($task->lastRunFailedAt())->format($dateFormat) ?? '--' }}
                            </span>
                        </span>
                        <br class="hidden xl:block">
                        <span class="ml-3 xl:ml-0">
                            <span class="w-15 xl:w-14 text-gray-500">⇁ Next run:</span>
                            <span class="date-width">{{ $task->nextRunAt()->format($dateFormat) }}</span>
                        </span>
                        <br class="xl:hidden">
                        <span class="xl:ml-3">
                            <span class="w-14 xl:w-15 text-gray-500">⇁ Grace time:</span>
                            <span class="date-width">{{ $task->graceTimeInMinutes() }} minutes</span>
                        </span>
                        @if ($usingOhDear)
                            <span class="ml-3">
                                <span class="text-gray-500">⇁ Registered at Oh Dear:</span>
                                @if ($task->isBeingMonitoredAtOhDear())
                                    <span class="ml-1 px-1 bg-green-700 text-white font-bold">Yes</span>
                                @else
                                    <span class="ml-1 px-1 bg-red-700 text-white font-bold">No</span>
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-gray-500 italic">There currently are no tasks being monitored!</div>
        @endforelse
    </div>
    @if ($usingOhDear)
        <div>
            Some tasks are not registered on <b class="text-white bg-red-700 px-1">oh dear</b>. You will not be notified when they do not run on time. <br>
            Run <span class="text-yellow font-bold">php artisan schedule-monitor:sync</span> to register them and receive notifications.
        </div>
    @endif
</div>
