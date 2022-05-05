@props(['task'])
<div class="space-x-1">
    @if ($task->name())
        <span>{{ $task->name() }}</span>
        <span class="text-gray-500 lowercase">({{ $task->type() }})</span>
    @else
        <span>{{ $task->type() }}</span>
    @endif
    <span class="text-gray-500">
        {{ str_repeat('.', (new \Termwind\Terminal)->width() - (
            strlen($task->name() . $task->type() . $task->humanReadableCron()) + ($task->name() && $task->type() ? 9 : 6)
        )) }}
    </span>
    <span class="text-gray-500">{{ $task->humanReadableCron() }}</span>
</div>
