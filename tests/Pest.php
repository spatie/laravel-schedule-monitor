<?php

use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Illuminate\Support\Str;

uses(TestCase::class, MatchesSnapshots::class)->in(__DIR__);

function createUuidRange(int $count)
{
    if ($count < 1) {
        throw new \Exception('Uuid range must be greater than 0');
    }

    Str::createUuidsUsingSequence(
        collect()
            ->range(1, $count)
            ->map(fn ($i) => "test-uuid-{$i}")
            ->toArray()
    );
}
