<?php

namespace Spatie\ScheduleMonitor\Tests\Commands;

use Spatie\ScheduleMonitor\Commands\CleanLogCommand;
use Spatie\ScheduleMonitor\Models\MonitoredScheduledTaskLogItem;
use Spatie\ScheduleMonitor\Tests\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Spatie\TestTime\TestTime;

class CleanLogTest extends TestCase
{
    use MatchesSnapshots;

    public function setUp(): void
    {
        parent::setUp();

        TestTime::freeze('Y-m-d H:i:s', '2020-01-01 00:00:00');
    }

    /** @test */
    public function by_default_it_will_delete_all_log_items_older_than_30_days()
    {
        foreach (range(1, 70) as $i) {
            factory(MonitoredScheduledTaskLogItem::class)->create([
                'created_at' => now(),
            ]);

            TestTime::addDay();
        }

        $this->artisan(CleanLogCommand::class)->assertExitCode(0);

        $this->assertCount(30, MonitoredScheduledTaskLogItem::get());

        $oldestLogItem = MonitoredScheduledTaskLogItem::orderBy('created_at')->first();

        $this->assertEquals('2020-02-10', $oldestLogItem->created_at->format('Y-m-d'));
    }
}
