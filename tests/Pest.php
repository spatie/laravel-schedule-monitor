<?php

use Illuminate\Support\Str;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

uses(TestCase::class, MatchesSnapshots::class)->in(__DIR__);

function useFakeUuids()
{
    Str::createUuidsUsing(function () use (&$next) {
        $next++;

        return "test-uuid-{$next}";
    });
}
