@props(['task'])
<div class="flex space-x-1">
    @if ($task->name())
        <span>{{ $task->name() }}</span>
        <span class="text-gray-500 lowercase">({{ $task->type() }})</span>
    @else
        <span>{{ $task->type() }}</span>
    @endif
    <span class="text-gray-500 flex-1 content-repeat-['.']"></span>
    <span class="text-gray-500">{{ $task->humanReadableCron() }}</span>
</div>
