<?php

namespace Spatie\ScheduleMonitor\Support\ScheduledTasks;

class MonitoredScheduledTasks
{
    /**
     * Multidimensional array to hold values grouped by class, instance id and property.
     *
     * Example:
     *
     * ```
     * [ '\Illuminate\Console\Scheduling\Event' => 'obj_234' => [ 'propertyName' => 'some_value' ]]]
     * ```
     *
     * @see self::makeKey()
     *
     * @var array<string,array<string,array<string,mixed>>>
     */
    protected array $store = [];

    public function setMonitorName(object $target, string $monitorName): void
    {
        $this->setProperty($target, 'monitorName', $monitorName);
    }

    public function getMonitorName(object $target): ?string
    {
        return $this->getProperty($target, 'monitorName');
    }

    public function setGraceTimeInMinutes(object $target, int $graceTimeInMinutes): void
    {
        $this->setProperty($target, 'graceTimeInMinutes', $graceTimeInMinutes);
    }

    public function getGraceTimeInMinutes(object $target): ?int
    {
        return $this->getProperty($target, 'graceTimeInMinutes');
    }

    public function setDoNotMonitor(object $target, bool $doNotMonitor = true): void
    {
        $this->setProperty($target, 'doNotMonitor', $doNotMonitor);
    }

    public function getDoNotMonitor(object $target): ?bool
    {
        return $this->getProperty($target, 'doNotMonitor');
    }

    public function setDoNotMonitorAtOhDear(object $target, bool $doNotMonitorAtOhDear = true): void
    {
        $this->setProperty($target, 'doNotMonitorAtOhDear', $doNotMonitorAtOhDear);
    }

    public function getDoNotMonitorAtOhDear(object $target): ?bool
    {
        return $this->getProperty($target, 'doNotMonitorAtOhDear');
    }

    public function setStoreOutputInDb(object $target, bool $storeOutputInDb = true): void
    {
        $this->setProperty($target, 'storeOutputInDb', $storeOutputInDb);
    }

    public function getStoreOutputInDb(object $target): ?bool
    {
        return $this->getProperty($target, 'storeOutputInDb');
    }


    protected function setProperty(object $target, string $key, mixed $value): void
    {
        data_set($this->store, $this->makeKey($target, $key), $value);
    }

    protected function getProperty(object $target, string $key): mixed
    {
        return data_get($this->store, $this->makeKey($target, $key));
    }

    protected function makeKey(object $target, string $key): array
    {
        return [
            $target::class,
            spl_object_hash($target),
            $key,
        ];
    }
}
