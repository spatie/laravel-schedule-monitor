# Changelog

All notable changes to `laravel-schedule-monitor` will be documented in this file

## 3.10.2 - 2025-02-21

### What's Changed

* Additional custom ping endpoint and config by @resohead in https://github.com/spatie/laravel-schedule-monitor/pull/123

### New Contributors

* @resohead made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/123

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.10.1...3.10.2

## 3.10.1 - 2025-02-21

### What's Changed

* Laravel 12.x Compatibility by @laravel-shift in https://github.com/spatie/laravel-schedule-monitor/pull/122

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.10.0...3.10.1

## 2.4.8 - 2025-02-17

### What's Changed

* Update MonitoredScheduledTask.php to get the failed response to be st… by @675076143 in https://github.com/spatie/laravel-schedule-monitor/pull/121

### New Contributors

* @675076143 made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/121

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/2.4.7...2.4.8

## 3.10.0 - 2025-02-05

### What's Changed

* Add support for a custom ping endpoint in Oh Dear by @mattiasgeniar in https://github.com/spatie/laravel-schedule-monitor/pull/119

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.9.2...3.10.0

## 3.9.2 - 2025-01-17

### What's Changed

* Use explicit nullable type is Task::nextRunAt by @bastien-phi in https://github.com/spatie/laravel-schedule-monitor/pull/117

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.9.1...3.9.2

## 3.9.1 - 2025-01-17

### What's Changed

* Fix CronExpression deprecation using constructor instead of factory method by @bastien-phi in https://github.com/spatie/laravel-schedule-monitor/pull/118

### New Contributors

* @bastien-phi made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/118

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.9.0...3.9.1

## 3.9.0 - 2025-01-06

### What's Changed

* Store schedule monitoring configurations in its own singleton by @m-bymike in https://github.com/spatie/laravel-schedule-monitor/pull/114

### New Contributors

* @m-bymike made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/114

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.8.2...3.9.0

## 3.8.2 - 2024-12-16

### What's Changed

* don't write to horizon config when not available by @Propaganistas in https://github.com/spatie/laravel-schedule-monitor/pull/116

### New Contributors

* @Propaganistas made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/116

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.8.1...3.8.2

## 3.8.1 - 2024-07-29

### What's Changed

* Fix prune link in config file comment by @pelmered in https://github.com/spatie/laravel-schedule-monitor/pull/113

### New Contributors

* @pelmered made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/113

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.8.0...3.8.1

## 3.8.0 - 2024-06-17

### What's Changed

* Update README.md typo by @acip in https://github.com/spatie/laravel-schedule-monitor/pull/111
* Make `graceTimeInMinutes` configurable by @faustbrian in https://github.com/spatie/laravel-schedule-monitor/pull/112

### New Contributors

* @acip made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/111
* @faustbrian made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/112

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.7.1...3.8.0

## 3.7.1 - 2024-03-28

### What's Changed

* Fix wrong lastRunFinishedTooLate behaviour when lastStartedAt and lastFinishedAt are within a second because of a very fast task by @mathiasmoser in https://github.com/spatie/laravel-schedule-monitor/pull/109

### New Contributors

* @mathiasmoser made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/109

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.7.0...3.7.1

## 3.7.0 - 2024-03-02

### What's Changed

* Laravel 11.x Compatibility by @laravel-shift in https://github.com/spatie/laravel-schedule-monitor/pull/106

### New Contributors

* @laravel-shift made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/106

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.6.0...3.7.0

## 3.6.0 - 2024-02-28

### What's Changed

* New method runsInBackground() added in  Spatie\ScheduleMonitor\Support\ScheduledTasks\Tasks\Task:class by @ravi289 in https://github.com/spatie/laravel-schedule-monitor/pull/105

### New Contributors

* @ravi289 made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/105

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.5.0...3.6.0

## 3.5.0 - 2024-01-26

### What's Changed

* Allow tasks to be monitored but not synced with oh dear by @oddvalue in https://github.com/spatie/laravel-schedule-monitor/pull/102

### New Contributors

* @oddvalue made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/102

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.4.3...3.5.0

## 3.4.3 - 2024-01-19

### What's Changed

* Update nunomaduro/termwind to 2.0 by @yoeriboven in https://github.com/spatie/laravel-schedule-monitor/pull/99

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.4.2...3.4.3

## 3.4.2 - 2023-12-14

### What's Changed

* fix: PHP warning about creation of dynamic properties by @Pr3d4dor in https://github.com/spatie/laravel-schedule-monitor/pull/98

### New Contributors

* @Pr3d4dor made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/98

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.4.1...3.4.2

## 3.4.1 - 2023-11-29

### What's Changed

* Update README.md by @robjbrain in https://github.com/spatie/laravel-schedule-monitor/pull/96
* Update MonitoredScheduledTask.php to get the failed response to be st… by @AKHIL-882 in https://github.com/spatie/laravel-schedule-monitor/pull/97

### New Contributors

* @robjbrain made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/96
* @AKHIL-882 made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/97

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.4.0...3.4.1

## 3.4.0 - 2023-08-01

### What's Changed

- Add note that syncing will remove other monitors by @keithbrink in https://github.com/spatie/laravel-schedule-monitor/pull/90
- Fix anchor in link to Laravel docs by @limenet in https://github.com/spatie/laravel-schedule-monitor/pull/92
- Non destructive sync option (keep-old) by @keithbrink in https://github.com/spatie/laravel-schedule-monitor/pull/91

### New Contributors

- @keithbrink made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/90
- @limenet made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/92

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.3.0...3.4.0

## 3.3.0 - 2023-05-24

### What's Changed

- Add a boolean parameter to the doNotMonitor function by @bilfeldt in https://github.com/spatie/laravel-schedule-monitor/pull/88

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.2.1...3.3.0

## 3.2.1 - 2023-02-01

- fix silent by default

## 3.2.0 - 2023-02-01

- silence jobs by default

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.1.1...3.2.0

## 3.1.1 - 2023-01-23

- support L10

## 3.0.4 - 2022-10-02

- update deps

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.0.3...3.0.4

## 3.0.3 - 2022-05-13

## What's Changed

- fix: Use `flex` and `content-repeat` on Termwind outputs. by @xiCO2k in https://github.com/spatie/laravel-schedule-monitor/pull/76

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.0.2...3.0.3

## 3.0.2 - 2022-05-05

## What's Changed

- Update readme about model pruning by @patrickbrouwers in https://github.com/spatie/laravel-schedule-monitor/pull/71
- PHPUnit to Pest Converter by @freekmurze in https://github.com/spatie/laravel-schedule-monitor/pull/73
- chore: add multitenancy documentation by @ju5t in https://github.com/spatie/laravel-schedule-monitor/pull/75
- Add Termwind to improve the Command Outputs. by @xiCO2k in https://github.com/spatie/laravel-schedule-monitor/pull/74

## New Contributors

- @ju5t made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/75
- @xiCO2k made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/74

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.0.1...3.0.2

## 3.0.1 - 2022-02-13

## What's Changed

- Fix return type by @SamuelNitsche in https://github.com/spatie/laravel-schedule-monitor/pull/70

## New Contributors

- @SamuelNitsche made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/70

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/3.0.0...3.0.1

## 3.0.0 - 2022-01-14

- Support Laravel 9

## 2.4.7 - 2021-11-17

## What's Changed

- Update lorisleiva/cron-translator to version 0.3 by @bilfeldt in https://github.com/spatie/laravel-schedule-monitor/pull/67

## New Contributors

- @bilfeldt made their first contribution in https://github.com/spatie/laravel-schedule-monitor/pull/67

**Full Changelog**: https://github.com/spatie/laravel-schedule-monitor/compare/2.4.6...2.4.7

## 2.4.6 - 2021-11-02

- Make sure retryUntil is returning a DateTime (#66)

## 2.4.5 - 2021-09-16

- take environments property into account for scheduled tasks (#64)

## 2.4.4 - 2021-09-07

- add `retryUntil` for PingOhdearJobs (#63)

## 2.4.3 - 2021-08-02

- automatically retry ping if OhDear had downtime (#54)

## 2.4.2 - 2021-07-22

- add link to docs

## 2.4.1 - 2021-06-15

- update user API token url (#50)

## 2.4.0 - 2021-06-10

- enable custom models

## 2.3.0 - 2021-05-13

- add `storeOutputInDb`

## 2.2.1 - 2021-03-29

- upgrade to latest lorisleiva/cron-translator version (#40)

## 2.2.0 - 2021-01-15

- throw an exception if pinging Oh Dear has failed [#37](https://github.com/spatie/laravel-schedule-monitor/pull/37)
- pass 0 instead of null parameters to Oh dear for Background tasks [#37](https://github.com/spatie/laravel-schedule-monitor/pull/37)

## 2.1.0 - 2020-12-04

- add support for PHP 8

## 2.0.2 - 2020-10-14

- drop support for Laravel 7
- fix command description

## 2.0.1 - 2020-10-06

- report right exit code for scheduled tasks in background

## 2.0.0 - 2020-09-29

- add support for timezones

## 1.0.4 - 2020-09-08

- add support for Laravel 8

## 1.0.3 - 2020-07-14

- fix link config file

## 1.0.2 - 2020-07-14

- add `CarbonImmutable` support (#3)

## 1.0.1 - 2020-07-12

- improve output of commands

## 1.0.0 - 2020-07-09

- initial release
