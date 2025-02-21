<?php

use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Illuminate\Support\Str;

uses(TestCase::class, MatchesSnapshots::class)->in(__DIR__);

function useFakeUuids()
{
    Str::createUuidsUsing(function () use (&$next) {
        $next++;
        return "test-uuid-{$next}";
    });
}
