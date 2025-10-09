<?php

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTask;

beforeEach(function () {
    $this->task = MonitoredScheduledTask::factory()->create(['name' => 'test-task']);
});

afterEach(function () {
    // Clean up any test files
    $testFiles = glob(storage_path('logs/test-*.log'));
    foreach ($testFiles as $file) {
    if (file_exists($file)) {
        @unlink($file);
    }
    }
});

/**
 * Helper function to call protected/private methods using reflection
 */
function callProtectedMethod($object, $method, ...$args)
{
    $reflection = new ReflectionClass($object);
    $method = $reflection->getMethod($method);
    $method->setAccessible(true);

    return $method->invoke($object, ...$args);
}

// extractFailureMessageFromOutput tests
it('extracts message from Exception: pattern', function () {
    $output = "Some output\nException: Something went wrong\nStack trace...";
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect($message)->toBe('Something went wrong');
});

it('extracts message from exception: pattern (case insensitive)', function () {
    $output = "Some output\nexception: Case insensitive match\nStack trace...";
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect($message)->toBe('Case insensitive match');
});

it('extracts message from Error: pattern', function () {
    $output = "Some output\nError: Fatal error occurred\nStack trace...";
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect($message)->toBe('Fatal error occurred');
});

it('extracts message from error: pattern (case insensitive)', function () {
    $output = "Some output\nerror: Case insensitive error\nStack trace...";
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect($message)->toBe('Case insensitive error');
});

it('uses last non-empty line as fallback', function () {
    $output = "Line 1\nLine 2\nLine 3 is the last";
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect($message)->toBe('Line 3 is the last');
});

it('returns generic message when output is null', function () {
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', null, 42);

    expect($message)->toBe('Command failed with exit code 42');
});

it('returns generic message when output is empty string', function () {
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', '', 1);

    expect($message)->toBe('Command failed with exit code 1');
});

it('truncates long messages to 255 characters', function () {
    $longMessage = str_repeat('A', 300);
    $output = "Exception: {$longMessage}";
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect(strlen($message))->toBe(255);
    expect($message)->toStartWith(str_repeat('A', 252)); // 255 - 3 for "..."
});

it('stops at newline in exception message', function () {
    $output = "Exception: First line\nSecond line should not be included";
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect($message)->toBe('First line');
});

it('takes first exception when multiple exist', function () {
    $output = "Exception: First exception\nSome text\nException: Second exception";
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect($message)->toBe('First exception');
});

it('handles multi-line output with exception in middle', function () {
    $output = <<<'OUTPUT'
Starting command...
Processing data...
Exception: Failed to process item
at SomeClass.php:123
Cleaning up...
OUTPUT;
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect($message)->toBe('Failed to process item');
});

it('handles whitespace-only output', function () {
    $output = "   \n\n\t\t\n   ";
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect($message)->toBe('Command failed with exit code 1');
});

it('prefers Exception over Error pattern', function () {
    $output = "Error: Some error\nException: Some exception";
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    expect($message)->toBe('Some exception');
});

it('handles RuntimeException format', function () {
    $output = <<<'OUTPUT'

   RuntimeException

  Database connection failed

  at vendor/laravel/framework/Database.php:42
OUTPUT;
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', $output, 1);

    // Should fall back to last non-empty line since there's no "Exception:" pattern
    expect($message)->not->toBeEmpty();
});

it('includes correct exit code in fallback message', function () {
    $message = callProtectedMethod($this->task, 'extractFailureMessageFromOutput', null, 127);

    expect($message)->toBe('Command failed with exit code 127');
});

// getBackgroundTaskOutput tests
it('reads output when file exists', function () {
    $outputFile = storage_path('logs/test-background-output.log');
    File::ensureDirectoryExists(dirname($outputFile));
    File::put($outputFile, 'Background task output content');

    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $scheduledEvent->output = $outputFile;

    $output = callProtectedMethod($this->task, 'getBackgroundTaskOutput', $scheduledEvent);

    expect($output)->toBe('Background task output content');

    File::delete($outputFile);
});

it('returns null when output property is null', function () {
    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $scheduledEvent->output = null;

    $output = callProtectedMethod($this->task, 'getBackgroundTaskOutput', $scheduledEvent);

    expect($output)->toBeNull();
});

it('returns null when output is default output', function () {
    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $defaultOutput = $scheduledEvent->getDefaultOutput();
    $scheduledEvent->output = $defaultOutput;

    $output = callProtectedMethod($this->task, 'getBackgroundTaskOutput', $scheduledEvent);

    expect($output)->toBeNull();
});

it('returns null when file does not exist', function () {
    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $scheduledEvent->output = '/non/existent/file.log';

    $output = callProtectedMethod($this->task, 'getBackgroundTaskOutput', $scheduledEvent);

    expect($output)->toBeNull();
});

it('returns null when file exists but is empty', function () {
    $outputFile = storage_path('logs/test-empty-output.log');
    File::ensureDirectoryExists(dirname($outputFile));
    File::put($outputFile, '');

    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $scheduledEvent->output = $outputFile;

    $output = callProtectedMethod($this->task, 'getBackgroundTaskOutput', $scheduledEvent);

    expect($output)->toBeNull();

    File::delete($outputFile);
});

// getEventTaskOutput tests
it('returns null when storeOutputInDb is false', function () {
    $outputFile = storage_path('logs/test-event-output.log');
    File::ensureDirectoryExists(dirname($outputFile));
    File::put($outputFile, 'Event task output');

    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $scheduledEvent->output = $outputFile;
    // Don't call storeOutputInDb()

    $event = new ScheduledTaskFinished($scheduledEvent, 123);

    $output = $this->task->getEventTaskOutput($event);

    expect($output)->toBeNull();

    File::delete($outputFile);
});

it('returns output when storeOutputInDb is true', function () {
    $outputFile = storage_path('logs/test-event-store-output.log');
    File::ensureDirectoryExists(dirname($outputFile));
    File::put($outputFile, 'Stored event output');

    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $scheduledEvent->output = $outputFile;
    $scheduledEvent->storeOutputInDb();

    $event = new ScheduledTaskFinished($scheduledEvent, 123);

    $output = $this->task->getEventTaskOutput($event);

    expect($output)->toBe('Stored event output');

    File::delete($outputFile);
});

it('returns null when file does not exist even with storeOutputInDb', function () {
    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $scheduledEvent->output = '/non/existent/event-file.log';
    $scheduledEvent->storeOutputInDb();

    $event = new ScheduledTaskFinished($scheduledEvent, 123);

    $output = $this->task->getEventTaskOutput($event);

    expect($output)->toBeNull();
});

it('event task returns null when output is null', function () {
    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $scheduledEvent->output = null;
    $scheduledEvent->storeOutputInDb();

    $event = new ScheduledTaskFinished($scheduledEvent, 123);

    $output = $this->task->getEventTaskOutput($event);

    expect($output)->toBeNull();
});

it('event task returns null when output is default output', function () {
    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $defaultOutput = $scheduledEvent->getDefaultOutput();
    $scheduledEvent->output = $defaultOutput;
    $scheduledEvent->storeOutputInDb();

    $event = new ScheduledTaskFinished($scheduledEvent, 123);

    $output = $this->task->getEventTaskOutput($event);

    expect($output)->toBeNull();
});

it('handles file with only whitespace', function () {
    $outputFile = storage_path('logs/test-whitespace-output.log');
    File::ensureDirectoryExists(dirname($outputFile));
    File::put($outputFile, "   \n\n\t\t\n   ");

    $schedule = app(Schedule::class);
    $scheduledEvent = $schedule->command('test:command');
    $scheduledEvent->output = $outputFile;
    $scheduledEvent->storeOutputInDb();

    $event = new ScheduledTaskFinished($scheduledEvent, 123);

    $output = $this->task->getEventTaskOutput($event);

    // Should return the whitespace content, not null
    expect($output)->toBe("   \n\n\t\t\n   ");

    File::delete($outputFile);
});
