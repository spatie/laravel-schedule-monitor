<?php

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\ScheduleMonitor\Jobs\PingOhDearJob;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;

beforeEach(function () {
    config()->set('schedule-monitor.oh_dear.retry_delay_ms', 0);

    $this->logItem = MonitoredScheduledTaskLogItem::factory()->create([
        'type' => MonitoredScheduledTaskLogItem::TYPE_FINISHED,
        'meta' => ['runtime' => 12.34, 'exit_code' => 0, 'memory' => 12345],
    ]);
});

it('does not log when debug logging is disabled and ping fails', function () {
    Http::fake(['ping.ohdear.app/*' => Http::response('Server Error', 500)]);

    Log::shouldReceive('warning')->never();

    dispatch(new PingOhDearJob($this->logItem));
})->throws(RequestException::class);

it('logs diagnostics when debug logging is enabled and ping fails', function () {
    config()->set('schedule-monitor.oh_dear.debug_logging', true);

    Http::fake(['ping.ohdear.app/*' => Http::response('Server Error', 500)]);

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function (string $message, array $context) {
            expect($message)->toContain('PingOhDearJob failed');
            expect($context['request']['url'])->toContain('ping.ohdear.app');
            expect($context['request'])->toHaveKey('data');
            expect($context)->toHaveKey('exception');

            return true;
        });

    dispatch(new PingOhDearJob($this->logItem));
})->throws(RequestException::class);

it('does not log when ping succeeds', function () {
    config()->set('schedule-monitor.oh_dear.debug_logging', true);

    Http::fake(['ping.ohdear.app/*' => Http::response('ok', 200)]);

    Log::shouldReceive('warning')->never();

    dispatch(new PingOhDearJob($this->logItem));
});
